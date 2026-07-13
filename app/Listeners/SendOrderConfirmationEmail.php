<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Mail\OrderConfirmationMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie l'email de confirmation après une commande payée (sans bloquer le paiement).
 */
class SendOrderConfirmationEmail
{
  /**
   * Traite l'événement OrderPlaced et envoie l'email au client.
   *
   * @param OrderPlaced $event Événement commande confirmée
   * @return void
   */
  public function handle(OrderPlaced $event): void
  {
    $order = $event->order->load(['items', 'shippingAddress', 'payment', 'user']);

    $email = $order->user?->email
      ?? ($order->payment?->metadata['customer_email'] ?? null);

    if (!$email) {
      return;
    }

    try {
      Mail::to($email)->send(new OrderConfirmationMail($order));
    } catch (\Throwable $exception) {
      Log::error('Échec envoi email confirmation commande', [
        'order' => $order->order_number,
        'email' => $email,
        'error' => $exception->getMessage(),
      ]);
    }
  }
}
