<?php

namespace App\Services;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Prévient la cliente sur WhatsApp quand le statut d'une commande passée
 * par le bot change (paiement reçu, en préparation, expédiée, livrée,
 * annulée) : message envoyé par Callbell, et webhook sortant signé en option.
 */
class BotNotifier
{
  /**
   * @param BotCommerceService $bot Format des commandes
   * @param WhatsAppNotifier $whatsapp Envoi Callbell
   * @param CurrencyService $currency Montants
   * @param SiteSettingsService $settings Adresse de retrait
   */
  public function __construct(
    private readonly BotCommerceService $bot,
    private readonly WhatsAppNotifier $whatsapp,
    private readonly CurrencyService $currency,
    private readonly SiteSettingsService $settings
  ) {
  }

  /**
   * Programme l'envoi après la validation de la transaction en cours.
   *
   * @param Order $order Commande dont le statut vient de changer
   * @return void
   */
  public function orderStatusChanged(Order $order): void
  {
    if ($order->source !== 'whatsapp') {
      return;
    }

    DB::afterCommit(function () use ($order): void {
      $fresh = $order->fresh(['items', 'payment', 'user', 'addresses']);

      if (!$fresh) {
        return;
      }

      $phone = $fresh->addresses->first()?->phone ?? $fresh->user?->phone;

      if ($phone) {
        $text = $this->messageFor($fresh);

        if ($text !== null) {
          $this->whatsapp->sendText((string) $phone, $text);
        }
      }

      $this->sendWebhook($fresh, $phone ? (string) $phone : null);
    });
  }

  /**
   * Texte WhatsApp correspondant au nouveau statut.
   *
   * @param Order $order Commande à jour
   * @return string|null Message, ou null s'il n'y a rien à dire
   */
  public function messageFor(Order $order): ?string
  {
    $number = '*' . $order->order_number . '*';
    $total = $this->currency->formatOrderAmount((float) $order->total, $order->currency);
    $isPickup = $order->fulfillment_type === 'pickup';

    return match ($order->status) {
      OrderStatus::Paid => "✅ Paiement de {$total} bien reçu ! Votre commande {$number} est confirmée. Nous la préparons avec soin 💛",
      OrderStatus::Processing => $order->payment_method === PaymentMethod::Cod
        ? "👍 Votre commande {$number} est en préparation. Vous réglerez {$total} " . ($isPickup ? 'au retrait.' : 'à la livraison.')
        : "Votre commande {$number} est en préparation 🧴",
      OrderStatus::Shipped => $isPickup
        ? "🛍️ Votre commande {$number} est prête ! Vous pouvez venir la retirer : " . $this->settings->get('pickup_store_address', 'à la boutique') . '.'
        : "🚚 Votre commande {$number} est en route !" . ($order->tracking_number ? " Suivi : {$order->tracking_number}." : ''),
      OrderStatus::Delivered => "🎉 Votre commande {$number} a été livrée. Merci pour votre confiance ! Si vous avez une question sur l'utilisation de vos produits, écrivez-nous ici.",
      OrderStatus::Cancelled => "Votre commande {$number} a été annulée. Si c'est une erreur, répondez simplement à ce message.",
      default => null,
    };
  }

  /**
   * Webhook sortant optionnel (BOT_NOTIFY_URL), signé HMAC-SHA256.
   *
   * @param Order $order Commande à jour
   * @param string|null $phone Numéro de la cliente
   * @return void
   */
  private function sendWebhook(Order $order, ?string $phone): void
  {
    $url = (string) config('bot.notify_url');

    if ($url === '') {
      return;
    }

    $payload = [
      'event' => 'order.status_changed',
      'phone' => $phone ? '+' . $this->bot->normalizePhone($phone) : null,
      'customer_name' => $order->user?->name,
      'order' => $this->bot->orderPayload($order),
      'sent_at' => now()->toIso8601String(),
    ];

    $body = (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $secret = (string) config('bot.notify_secret');

    try {
      Http::timeout(5)
        ->withHeaders(array_filter([
          'X-Bot-Signature' => $secret !== '' ? hash_hmac('sha256', $body, $secret) : null,
        ]))
        ->withBody($body, 'application/json')
        ->post($url);
    } catch (\Throwable $exception) {
      Log::warning('Webhook bot WhatsApp non envoyé', [
        'order' => $order->order_number,
        'error' => $exception->getMessage(),
      ]);
    }
  }
}
