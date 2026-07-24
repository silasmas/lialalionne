<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Stripe\Checkout\Session;
use Stripe\Exception\SignatureVerificationException;
use Stripe\Stripe;
use Stripe\Webhook;
use UnexpectedValueException;

/**
 * Service d'intégration des paiements (FlexPay, Stripe fallback, webhooks).
 */
class PaymentService
{
  /**
   * @param OrderService $orderService Service commandes
   * @param SiteSettingsService $settings Service paramètres boutique
   * @param FlexPayService $flexPayService Passerelle FlexPay
   */
  public function __construct(
    private readonly OrderService $orderService,
    private readonly SiteSettingsService $settings,
    private readonly FlexPayService $flexPayService
  ) {
  }

  /**
   * Initie un paiement selon la méthode choisie sur la commande.
   *
   * @param Order $order Commande en attente de paiement
   * @return array{redirect_url: string, session_id: string|null, simulated: bool, pending?: bool}
   */
  public function initiate(Order $order): array
  {
    if (!$this->settings->isPaymentMethodEnabled($order->payment_method)) {
      throw ValidationException::withMessages([
        'payment' => 'Cette méthode de paiement n\'est pas disponible.',
      ]);
    }

    if ($this->flexPayService->isConfigured()) {
      return match ($order->payment_method) {
        PaymentMethod::MobileMoney => $this->initiateFlexPayMobilePage($order),
        default => $this->initiateFlexPayCard($order),
      };
    }

    return match ($order->payment_method) {
      PaymentMethod::MobileMoney => $this->initiateMobileMoneySimulated($order),
      default => $this->initiateCard($order),
    };
  }

  /**
   * Redirige vers la page Mobile Money (saisie téléphone + push FlexPay).
   *
   * @param Order $order Commande à payer
   * @return array{redirect_url: string, session_id: string, simulated: bool}
   */
  private function initiateFlexPayMobilePage(Order $order): array
  {
    $sessionId = 'LL-' . $order->order_number;

    $order->payment?->update([
      'transaction_id' => $sessionId,
      'metadata' => array_merge($order->payment->metadata ?? [], [
        'gateway' => 'flexpay',
        'flexpay_type' => 'mobile',
      ]),
    ]);

    return [
      'redirect_url' => route('shop.checkout', ['order' => $order->order_number]),
      'session_id' => $sessionId,
      'simulated' => false,
    ];
  }

  /**
   * Initie un paiement carte via FlexPay.
   *
   * @param Order $order Commande à payer
   * @return array{redirect_url: string, session_id: string|null, simulated: bool}
   */
  private function initiateFlexPayCard(Order $order): array
  {
    $result = $this->flexPayService->initiateCardPayment($order);

    $order->payment?->update([
      'transaction_id' => $result['reference'],
      'metadata' => array_merge($order->payment->metadata ?? [], [
        'gateway' => 'flexpay',
        'flexpay_type' => 'card',
        'flexpay_order_number' => $result['order_number'],
      ]),
    ]);

    return [
      'redirect_url' => $result['redirect_url'],
      'session_id' => $result['reference'],
      'simulated' => false,
    ];
  }

  /**
   * Envoie la requête push Mobile Money FlexPay (sans confirmer la commande).
   *
   * @param Order $order Commande en attente
   * @param string $phone Numéro Mobile Money
   * @param MobileMoneyOperator|null $operator Opérateur sélectionné
   * @return array{order_number: string, message: string}
   */
  public function requestFlexPayMobilePayment(
    Order $order,
    string $phone,
    ?\App\Enums\MobileMoneyOperator $operator = null
  ): array {
    if (!$this->flexPayService->isConfigured()) {
      $this->confirmMobileMoney($order, $phone, ['confirmed_via' => 'simulated']);

      return [
        'order_number' => $order->payment?->transaction_id ?? '',
        'message' => 'Paiement simulé confirmé.',
      ];
    }

    $result = $this->flexPayService->initiateMobilePayment($order, $phone);

    $order->payment?->update([
      'transaction_id' => $result['reference'],
      'metadata' => array_merge($order->payment->metadata ?? [], [
        'gateway' => 'flexpay',
        'flexpay_type' => 'mobile',
        'flexpay_order_number' => $result['order_number'],
        'mobile_money_phone' => $phone,
        'mobile_money_operator' => $operator?->value ?? ($order->payment->metadata['mobile_money_operator'] ?? null),
      ]),
    ]);

    return [
      'order_number' => $result['order_number'],
      'message' => $result['message'],
    ];
  }

  /**
   * Vérifie FlexPay et confirme la commande si succès.
   *
   * @param Order $order Commande en attente
   * @return Order Commande mise à jour
   */
  public function verifyAndConfirmFlexPay(Order $order): Order
  {
    $flexOrderNumber = $order->payment?->metadata['flexpay_order_number'] ?? null;

    if (!$flexOrderNumber || !$this->flexPayService->isConfigured()) {
      return $order->fresh(['items', 'addresses', 'payment']);
    }

    $check = $this->flexPayService->checkTransaction($flexOrderNumber);

    if (!$check['success']) {
      throw ValidationException::withMessages([
        'payment' => 'Paiement non confirmé. Validez le push sur votre téléphone puis réessayez.',
      ]);
    }

    return $this->orderService->confirmPayment(
      $order,
      $flexOrderNumber,
      ['confirmed_via' => 'flexpay_check', 'flexpay_reference' => $check['reference']]
    );
  }

  /**
   * Initie un paiement par carte bancaire via Stripe (fallback).
   *
   * @param Order $order Commande à payer
   * @return array{redirect_url: string, session_id: string|null, simulated: bool}
   */
  private function initiateCard(Order $order): array
  {
    $secret = config('services.stripe.secret');

    if (empty($secret)) {
      return $this->initiateSimulated($order, 'card');
    }

    Stripe::setApiKey($secret);
    $order->load('items', 'payment');

    $lineItems = [];

    // Stripe n'accepte pas de ligne négative : avec remise, on facture le total consolidé.
    if ((float) $order->discount_amount > 0) {
      $description = $order->coupon_code
        ? 'Remise code ' . $order->coupon_code . ' appliquée'
        : 'Remise appliquée';

      $lineItems[] = [
        'price_data' => [
          'currency' => strtolower($order->currency),
          'unit_amount' => (int) round((float) $order->total * 100),
          'product_data' => [
            'name' => 'Commande ' . $order->order_number,
            'description' => $description,
          ],
        ],
        'quantity' => 1,
      ];
    } else {
      foreach ($order->items as $item) {
        $lineItems[] = [
          'price_data' => [
            'currency' => strtolower($order->currency),
            'unit_amount' => (int) round((float) $item->unit_price * 100),
            'product_data' => [
              'name' => $item->product_name . ($item->variant_name ? " ({$item->variant_name})" : ''),
              'metadata' => ['sku' => $item->sku],
            ],
          ],
          'quantity' => $item->quantity,
        ];
      }

      if ((float) $order->shipping_amount > 0) {
        $lineItems[] = [
          'price_data' => [
            'currency' => strtolower($order->currency),
            'unit_amount' => (int) round((float) $order->shipping_amount * 100),
            'product_data' => ['name' => 'Frais de livraison'],
          ],
          'quantity' => 1,
        ];
      }
    }

    $customerEmail = $order->payment?->metadata['customer_email'] ?? null;

    $session = Session::create([
      'mode' => 'payment',
      'customer_email' => $customerEmail,
      'line_items' => $lineItems,
      'success_url' => route('checkout.success') . '?session_id={CHECKOUT_SESSION_ID}',
      'cancel_url' => route('checkout.cancel', ['order' => $order->order_number]),
      'metadata' => [
        'order_id' => (string) $order->id,
        'order_number' => $order->order_number,
      ],
    ]);

    $order->payment?->update([
      'transaction_id' => $session->id,
      'metadata' => array_merge($order->payment->metadata ?? [], [
        'stripe_session_id' => $session->id,
        'gateway' => 'stripe',
      ]),
    ]);

    return [
      'redirect_url' => $session->url,
      'session_id' => $session->id,
      'simulated' => false,
    ];
  }

  /**
   * Page interne Mobile Money en mode simulation.
   *
   * @param Order $order Commande à payer
   * @return array{redirect_url: string, session_id: string, simulated: bool}
   */
  private function initiateMobileMoneySimulated(Order $order): array
  {
    $sessionId = 'mm_' . $order->order_number;

    $order->payment?->update([
      'transaction_id' => $sessionId,
      'metadata' => array_merge($order->payment->metadata ?? [], [
        'gateway' => 'simulated_mobile_money',
        'simulated' => true,
      ]),
    ]);

    return [
      'redirect_url' => route('shop.checkout', ['order' => $order->order_number]),
      'session_id' => $sessionId,
      'simulated' => true,
    ];
  }

  /**
   * Confirme un paiement via l'ID de session (Stripe, FlexPay référence ou simulation).
   *
   * @param string $sessionId Identifiant transaction
   * @return Order Commande confirmée
   */
  public function confirmCheckoutSession(string $sessionId): Order
  {
    $order = Order::query()
      ->whereHas('payment', fn ($q) => $q->where('transaction_id', $sessionId))
      ->first();

    if (!$order) {
      $order = Order::query()
        ->whereHas('payment', fn ($q) => $q->where('metadata->flexpay_order_number', $sessionId))
        ->firstOrFail();
    }

    if ($order->status !== OrderStatus::Pending) {
      return $order->fresh(['items', 'addresses', 'payment']);
    }

    $gateway = $order->payment?->metadata['gateway'] ?? null;

    if ($gateway === 'flexpay') {
      return $this->verifyAndConfirmFlexPay($order);
    }

    if (str_starts_with($sessionId, 'mm_') || str_starts_with($sessionId, 'sim_')) {
      return $this->orderService->confirmPayment($order, $sessionId, [
        'confirmed_via' => 'simulated_return',
      ]);
    }

    $secret = config('services.stripe.secret');

    if (empty($secret)) {
      return $this->orderService->confirmPayment($order, $sessionId, [
        'confirmed_via' => 'simulated',
      ]);
    }

    Stripe::setApiKey($secret);
    $session = Session::retrieve($sessionId);

    if ($session->payment_status !== 'paid') {
      throw ValidationException::withMessages([
        'payment' => 'Le paiement n\'a pas été confirmé.',
      ]);
    }

    $orderId = (int) ($session->metadata['order_id'] ?? 0);
    $order = Order::query()->findOrFail($orderId);

    return $this->orderService->confirmPayment($order, $session->payment_intent ?? $sessionId, [
      'stripe_session_id' => $sessionId,
      'confirmed_via' => 'checkout_return',
    ]);
  }

  /**
   * Confirme un paiement Mobile Money en simulation locale uniquement.
   *
   * @param Order $order Commande concernée
   * @param string|null $phone Numéro Mobile Money saisi
   * @param array<string, mixed> $metadata Métadonnées gateway
   * @return Order Commande confirmée
   */
  public function confirmMobileMoney(Order $order, ?string $phone = null, array $metadata = []): Order
  {
    if ($order->status !== OrderStatus::Pending) {
      return $order->fresh(['items', 'addresses', 'payment']);
    }

    $transactionId = $order->payment?->transaction_id ?? ('mm_' . $order->order_number);

    return $this->orderService->confirmPayment($order, $transactionId, array_merge([
      'confirmed_via' => 'mobile_money_simulated',
      'mobile_money_phone' => $phone,
    ], $metadata));
  }

  /**
   * Traite le callback FlexPay (Mobile Money ou carte).
   *
   * @param Request $request Requête HTTP callback
   * @return void
   */
  public function handleFlexPayCallback(Request $request): void
  {
    $payload = $request->all();

    if (!$this->flexPayService->isSuccessfulCallback($payload)) {
      Log::info('FlexPay callback non réussi', $payload);

      return;
    }

    $reference = $this->flexPayService->callbackReference($payload);

    if (!$reference) {
      return;
    }

    $order = Order::query()
      ->whereHas('payment', fn ($q) => $q->where('transaction_id', $reference))
      ->first();

    if (!$order || $order->status !== OrderStatus::Pending) {
      return;
    }

    $this->orderService->confirmPayment(
      $order,
      $this->flexPayService->callbackOrderNumber($payload) ?? $reference,
      [
        'confirmed_via' => 'flexpay_webhook',
        'provider_reference' => $payload['provider_reference'] ?? null,
      ]
    );
  }

  /**
   * Traite le webhook Stripe (checkout.session.completed).
   *
   * @param Request $request Requête HTTP webhook
   * @return void
   */
  public function handleStripeWebhook(Request $request): void
  {
    $webhookSecret = config('services.stripe.webhook_secret');

    if (empty($webhookSecret)) {
      Log::warning('Webhook Stripe reçu sans STRIPE_WEBHOOK_SECRET configuré.');

      return;
    }

    $payload = $request->getContent();
    $signature = $request->header('Stripe-Signature');

    try {
      $event = Webhook::constructEvent($payload, $signature, $webhookSecret);
    } catch (UnexpectedValueException | SignatureVerificationException $exception) {
      Log::error('Webhook Stripe invalide', ['message' => $exception->getMessage()]);
      abort(400, 'Invalid payload');
    }

    if ($event->type !== 'checkout.session.completed') {
      return;
    }

    $session = $event->data->object;
    $orderId = (int) ($session->metadata->order_id ?? 0);

    if (!$orderId) {
      return;
    }

    $order = Order::query()->find($orderId);

    if (!$order || $order->status !== OrderStatus::Pending) {
      return;
    }

    $this->orderService->confirmPayment(
      $order,
      $session->payment_intent ?? $session->id,
      ['confirmed_via' => 'stripe_webhook', 'stripe_session_id' => $session->id]
    );
  }

  /**
   * Mode simulation locale sans passerelle (développement).
   *
   * @param Order $order Commande à payer
   * @param string $method Méthode simulée
   * @return array{redirect_url: string, session_id: string, simulated: bool}
   */
  private function initiateSimulated(Order $order, string $method = 'card'): array
  {
    $sessionId = 'sim_' . $order->order_number;

    $order->payment?->update([
      'transaction_id' => $sessionId,
      'metadata' => array_merge($order->payment->metadata ?? [], [
        'simulated' => true,
        'gateway' => $method,
      ]),
    ]);

    $this->orderService->confirmPayment($order, $sessionId, [
      'confirmed_via' => 'simulated',
    ]);

    return [
      'redirect_url' => route('checkout.success', ['session_id' => $sessionId]),
      'session_id' => $sessionId,
      'simulated' => true,
    ];
  }

  /**
   * @deprecated Utiliser handleStripeWebhook()
   */
  public function handleWebhook(Request $request): void
  {
    $this->handleStripeWebhook($request);
  }

  /**
   * @deprecated Utiliser handleFlexPayCallback()
   */
  public function handleMobileMoneyWebhook(Request $request): void
  {
    $this->handleFlexPayCallback($request);
  }
}
