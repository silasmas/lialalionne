<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Events\OrderPlaced;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Service de création et gestion des commandes.
 */
class OrderService
{
  /**
   * @param StockService $stockService Service de gestion des stocks
   * @param CartService $cartService Service panier
   * @param CurrencyService $currencyService Service devises
   * @param CouponService $couponService Service codes promo
   */
  public function __construct(
    private readonly StockService $stockService,
    private readonly CartService $cartService,
    private readonly CurrencyService $currencyService,
    private readonly CouponService $couponService
  ) {
  }

  /**
   * Crée une commande depuis le checkout (statut en attente de paiement).
   *
   * @param Cart $cart Panier source
   * @param array<string, mixed> $data Données adresse, livraison, client
   * @return Order Commande créée
   */
  public function createFromCheckout(Cart $cart, array $data = []): Order
  {
    $cart->load('items.product', 'items.variant');

    if ($cart->items->isEmpty()) {
      throw ValidationException::withMessages([
        'cart' => 'Votre panier est vide.',
      ]);
    }

    foreach ($cart->items as $item) {
      if (!$this->stockService->isAvailable($item->product, $item->quantity, $item->variant)) {
        throw ValidationException::withMessages([
          'stock' => "Stock insuffisant pour « {$item->product->name} ».",
        ]);
      }
    }

    return DB::transaction(function () use ($cart, $data) {
      $currency = strtoupper((string) ($data['currency'] ?? $this->currencyService->selectedCurrency()));
      $fulfillmentType = (string) ($data['fulfillment_type'] ?? 'delivery');

      $subtotalEur = $cart->subtotal();
      $shippingEur = $fulfillmentType === 'pickup' ? 0.0 : (float) ($data['shipping_amount'] ?? 0);
      $taxEur = (float) ($data['tax_amount'] ?? 0);

      $userId = $data['user_id'] ?? Auth::id();
      $user = $userId ? User::query()->find($userId) : null;

      $coupon = null;
      $discountEur = 0.0;
      $couponCode = null;

      if (!empty($data['coupon_code'])) {
        $coupon = $this->couponService->validateForCheckout(
          (string) $data['coupon_code'],
          $subtotalEur,
          $user
        );
        $discountEur = $this->couponService->calculateDiscountEur($coupon, $subtotalEur);
        $couponCode = $coupon->code;
      } elseif (isset($data['discount_amount'])) {
        $discountEur = max(0, (float) $data['discount_amount']);
      }

      $subtotal = $this->currencyService->convertFromEur($subtotalEur, $currency);
      $shippingAmount = $this->currencyService->convertFromEur($shippingEur, $currency);
      $discountAmount = $this->currencyService->convertFromEur($discountEur, $currency);
      $taxAmount = $this->currencyService->convertFromEur($taxEur, $currency);
      $total = max(0, $subtotal + $shippingAmount + $taxAmount - $discountAmount);

      $order = Order::query()->create([
        'order_number' => $this->generateOrderNumber(),
        'user_id' => $userId,
        'status' => OrderStatus::Pending,
        'payment_method' => $data['payment_method'] ?? PaymentMethod::Stripe,
        'fulfillment_type' => $fulfillmentType,
        'subtotal' => $subtotal,
        'shipping_amount' => $shippingAmount,
        'discount_amount' => $discountAmount,
        'tax_amount' => $taxAmount,
        'total' => $total,
        'currency' => $currency,
        'notes' => $data['notes'] ?? null,
        'coupon_id' => $coupon?->id,
        'coupon_code' => $couponCode,
      ]);

      if ($coupon instanceof Coupon) {
        $this->couponService->recordUsage($coupon);
      }

      foreach ($cart->items as $item) {
        $unitPrice = $this->currencyService->convertFromEur((float) $item->unit_price, $currency);
        $lineTotal = $this->currencyService->convertFromEur((float) $item->lineTotal(), $currency);

        $order->items()->create([
          'product_id' => $item->product_id,
          'product_variant_id' => $item->product_variant_id,
          'product_name' => $item->product->name,
          'variant_name' => $item->variant?->name,
          'sku' => $item->variant?->sku ?? $item->product->sku,
          'quantity' => $item->quantity,
          'unit_price' => $unitPrice,
          'total_price' => $lineTotal,
        ]);
      }

      OrderAddress::query()->create([
        'order_id' => $order->id,
        'type' => $fulfillmentType === 'pickup' ? 'pickup' : 'shipping',
        'first_name' => $data['first_name'],
        'last_name' => $data['last_name'],
        'phone' => $data['phone'] ?? null,
        'address_line_1' => $data['address_line_1'],
        'address_line_2' => $data['address_line_2'] ?? null,
        'city' => $data['city'],
        'state' => $data['state'] ?? null,
        'postal_code' => $data['postal_code'],
        'country' => $data['country'] ?? 'FR',
      ]);

      Payment::query()->create([
        'order_id' => $order->id,
        'method' => $data['payment_method'] ?? PaymentMethod::Stripe,
        'status' => PaymentStatus::Pending,
        'amount' => $total,
        'currency' => $currency,
        'metadata' => array_filter([
          'customer_email' => $data['customer_email'] ?? null,
          'shipping_rate_id' => $data['shipping_rate_id'] ?? null,
          'fulfillment_type' => $fulfillmentType,
          'rate_eur' => $this->currencyService->getRateFromEur($currency),
          'mobile_money_operator' => $data['mobile_money_operator'] ?? null,
          'mobile_money_phone' => $data['mobile_money_phone'] ?? null,
        ], fn ($value) => $value !== null && $value !== ''),
      ]);

      return $order->fresh(['items', 'addresses', 'payment']);
    });
  }

  /**
   * Confirme le paiement d'une commande (webhook ou retour Stripe).
   *
   * @param Order $order Commande à confirmer
   * @param string|null $transactionId Identifiant transaction externe
   * @param array<string, mixed> $metadata Métadonnées paiement
   * @return Order Commande mise à jour
   */
  public function confirmPayment(
    Order $order,
    ?string $transactionId = null,
    array $metadata = []
  ): Order {
    if ($order->status !== OrderStatus::Pending) {
      return $order->fresh(['items', 'addresses', 'payment']);
    }

    return DB::transaction(function () use ($order, $transactionId, $metadata) {
      $order->load('items.product', 'items.variant');

      foreach ($order->items as $item) {
        $product = $item->product;

        if (!$product) {
          continue;
        }

        $this->stockService->decrement($product, $item->quantity, $item->variant);
      }

      $order->update(['status' => OrderStatus::Paid]);

      $payment = $order->payment;
      if ($payment) {
        $payment->update([
          'status' => PaymentStatus::Paid,
          'transaction_id' => $transactionId ?? $payment->transaction_id,
          'metadata' => array_merge($payment->metadata ?? [], $metadata),
          'paid_at' => now(),
        ]);
      }

      $cart = Cart::query()
        ->when($order->user_id, fn ($q) => $q->where('user_id', $order->user_id))
        ->when(!$order->user_id, fn ($q) => $q->where('session_id', session()->getId()))
        ->first();

      if ($cart) {
        $this->cartService->clear($cart);
      }

      $order = $order->fresh(['items', 'addresses', 'payment', 'user']);

      OrderPlaced::dispatch($order);

      return $order;
    });
  }

  /**
   * Génère un numéro de commande unique.
   *
   * @return string Numéro au format LL-XXXXXXXX
   */
  private function generateOrderNumber(): string
  {
    return 'LL-' . strtoupper(Str::random(8));
  }
}
