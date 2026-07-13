<?php

namespace App\Listeners;

use App\Events\OrderShipped;
use App\Mail\OrderShipmentMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Envoie l'email d'expédition lorsqu'un numéro de suivi est renseigné.
 */
class SendOrderShipmentEmail
{
  /**
   * Traite l'événement OrderShipped et notifie le client par email.
   *
   * @param OrderShipped $event Événement expédition
   * @return void
   */
  public function handle(OrderShipped $event): void
  {
    $order = $event->order->load(['shippingAddress', 'payment', 'user']);

    $email = $order->user?->email
      ?? ($order->payment?->metadata['customer_email'] ?? null);

    if (!$email) {
      return;
    }

    try {
      Mail::to($email)->send(new OrderShipmentMail($order));

      $order->updateQuietly([
        'shipment_notified_tracking' => $order->tracking_number,
      ]);
    } catch (\Throwable $exception) {
      Log::error('Échec envoi email expédition commande', [
        'order' => $order->order_number,
        'email' => $email,
        'error' => $exception->getMessage(),
      ]);
    }
  }
}
