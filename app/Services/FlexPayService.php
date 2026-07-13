<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * Intégration passerelle FlexPay (Mobile Money + carte bancaire RDC).
 */
class FlexPayService
{
  /**
   * Indique si FlexPay est configuré (token + marchand).
   *
   * @return bool True si prêt pour production
   */
  public function isConfigured(): bool
  {
    return !empty(config('services.flexpay.token'))
      && !empty(config('services.flexpay.merchant'));
  }

  /**
   * Initie un paiement Mobile Money FlexPay (push USSD).
   *
   * @param Order $order Commande en attente
   * @param string $phone Numéro 243XXXXXXXXX
   * @return array{order_number: string, message: string}
   */
  public function initiateMobilePayment(Order $order, string $phone): array
  {
    $reference = $this->buildReference($order);
    $phone = $this->normalizePhone($phone);

    $response = Http::withToken(config('services.flexpay.token'))
      ->acceptJson()
      ->post(config('services.flexpay.gateway_mobile'), [
        'merchant' => config('services.flexpay.merchant'),
        'type' => '1',
        'phone' => $phone,
        'reference' => $reference,
        'amount' => $this->formatAmount($order->total),
        'currency' => strtoupper($order->currency),
        'callbackUrl' => route('payment.webhook.flexpay'),
      ]);

    $body = $response->json() ?? [];

    if (!$response->successful() || ($body['code'] ?? '1') !== '0') {
      Log::error('FlexPay mobile initiate failed', ['body' => $body, 'status' => $response->status()]);
      throw ValidationException::withMessages([
        'phone' => $body['message'] ?? 'Impossible d\'initier le paiement Mobile Money.',
      ]);
    }

    return [
      'order_number' => (string) ($body['orderNumber'] ?? $reference),
      'message' => (string) ($body['message'] ?? 'Validez le paiement sur votre téléphone.'),
      'reference' => $reference,
    ];
  }

  /**
   * Initie un paiement par carte via la passerelle FlexPay.
   *
   * @param Order $order Commande en attente
   * @return array{redirect_url: string, order_number: string|null}
   */
  public function initiateCardPayment(Order $order): array
  {
    $reference = $this->buildReference($order);

    $response = Http::withToken(config('services.flexpay.token'))
      ->acceptJson()
      ->post(config('services.flexpay.gateway_card'), [
        'merchant' => config('services.flexpay.merchant'),
        'reference' => $reference,
        'amount' => $this->formatAmount($order->total),
        'currency' => strtoupper($order->currency),
        'description' => 'Commande ' . $order->order_number,
        'callbackUrl' => route('payment.webhook.flexpay'),
        'approveUrl' => route('checkout.success') . '?session_id=' . $reference,
        'cancelUrl' => route('checkout.cancel', ['order' => $order->order_number]),
        'declineUrl' => route('checkout.cancel', ['order' => $order->order_number]),
        'homeUrl' => route('home'),
      ]);

    $body = $response->json() ?? [];

    if (!$response->successful()) {
      Log::error('FlexPay card initiate failed', ['body' => $body, 'status' => $response->status()]);
      throw ValidationException::withMessages([
        'payment' => $body['message'] ?? 'Impossible d\'initier le paiement par carte.',
      ]);
    }

    $redirectUrl = $body['url'] ?? $body['link'] ?? $body['data']['link'] ?? null;

    if (!$redirectUrl) {
      throw ValidationException::withMessages([
        'payment' => 'La passerelle n\'a pas renvoyé d\'URL de paiement. Réessayez ou choisissez Mobile Money.',
      ]);
    }

    return [
      'redirect_url' => $redirectUrl,
      'order_number' => $body['orderNumber'] ?? null,
      'reference' => $reference,
    ];
  }

  /**
   * Vérifie le statut d'une transaction FlexPay.
   *
   * @param string $orderNumber Référence FlexPay (orderNumber)
   * @return array{success: bool, status: string|null, reference: string|null}
   */
  public function checkTransaction(string $orderNumber): array
  {
    $response = Http::withToken(config('services.flexpay.token'))
      ->acceptJson()
      ->get(config('services.flexpay.gateway_check'), [
        'orderNumber' => $orderNumber,
      ]);

    $body = $response->json() ?? [];

    if (($body['code'] ?? '1') !== '0') {
      return ['success' => false, 'status' => null, 'reference' => null];
    }

    $transaction = $body['transaction'] ?? [];
    $success = ($transaction['status'] ?? '1') === '0';

    return [
      'success' => $success,
      'status' => $transaction['status'] ?? null,
      'reference' => $transaction['reference'] ?? null,
    ];
  }

  /**
   * Traite le callback FlexPay (Mobile Money ou carte).
   *
   * @param array<string, mixed> $payload Corps POST callback
   * @return bool True si paiement réussi
   */
  public function isSuccessfulCallback(array $payload): bool
  {
    return (string) ($payload['code'] ?? '1') === '0';
  }

  /**
   * Extrait la référence marchand depuis le callback.
   *
   * @param array<string, mixed> $payload Corps POST callback
   * @return string|null Référence interne
   */
  public function callbackReference(array $payload): ?string
  {
    return $payload['reference'] ?? null;
  }

  /**
   * Extrait l'orderNumber FlexPay depuis le callback.
   *
   * @param array<string, mixed> $payload Corps POST callback
   * @return string|null Order number FlexPay
   */
  public function callbackOrderNumber(array $payload): ?string
  {
    return $payload['orderNumber'] ?? null;
  }

  /**
   * Génère une référence unique pour FlexPay.
   *
   * @param Order $order Commande source
   * @return string Référence transaction
   */
  private function buildReference(Order $order): string
  {
    return 'LL-' . $order->order_number;
  }

  /**
   * Normalise un numéro Mobile Money congolais.
   *
   * @param string $phone Numéro saisi
   * @return string Numéro format 243XXXXXXXXX
   */
  private function normalizePhone(string $phone): string
  {
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if (str_starts_with($digits, '0')) {
      $digits = '243' . substr($digits, 1);
    }

    if (!str_starts_with($digits, '243')) {
      $digits = '243' . $digits;
    }

    return $digits;
  }

  /**
   * Formate un montant pour l'API FlexPay.
   *
   * @param float|string $amount Montant décimal
   * @return string Montant string
   */
  private function formatAmount(float|string $amount): string
  {
    return number_format((float) $amount, 2, '.', '');
  }
}
