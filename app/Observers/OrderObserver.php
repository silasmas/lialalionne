<?php

namespace App\Observers;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Events\OrderShipped;
use App\Models\Order;
use App\Services\BotNotifier;
use App\Services\LoyaltyService;

/**
 * Observer des commandes : déclenche les notifications métier à la mise à jour.
 */
class OrderObserver
{
  /**
   * Déclenche l'email d'expédition quand un numéro de suivi est renseigné,
   * synchronise le paiement quand l'admin marque manuellement une commande
   * comme payée (cas du paiement à la livraison encaissé), et rembourse les
   * points de fidélité utilisés si la commande est annulée.
   *
   * @param Order $order Commande mise à jour
   * @return void
   */
  public function updated(Order $order): void
  {
    if ($order->wasChanged('status')) {
      app(BotNotifier::class)->orderStatusChanged($order);
    }

    if ($order->wasChanged('status') && $order->status === OrderStatus::Paid) {
      $payment = $order->payment;

      if ($payment && $payment->status !== PaymentStatus::Paid) {
        $payment->update([
          'status' => PaymentStatus::Paid,
          'paid_at' => $payment->paid_at ?? now(),
        ]);
      }
    }

    if ($order->wasChanged('status') && $order->status === OrderStatus::Cancelled) {
      app(LoyaltyService::class)->refundRedeemedPoints($order);
    }

    if (!$order->wasChanged('tracking_number')) {
      return;
    }

    $tracking = trim((string) $order->tracking_number);

    if ($tracking === '') {
      return;
    }

    $this->syncShippedState($order);

    if ($order->shipment_notified_tracking === $tracking) {
      return;
    }

    OrderShipped::dispatch($order);
  }

  /**
   * Marque la commande comme expédiée et horodate l'envoi si besoin.
   *
   * @param Order $order Commande dont le suivi vient d'être renseigné
   * @return void
   */
  private function syncShippedState(Order $order): void
  {
    $updates = [];

    if ($order->shipped_at === null) {
      $updates['shipped_at'] = now();
    }

    if (in_array($order->status, [OrderStatus::Pending, OrderStatus::Paid, OrderStatus::Processing], true)) {
      $updates['status'] = OrderStatus::Shipped;
    }

    if ($updates === []) {
      return;
    }

    $order->updateQuietly($updates);
    $order->refresh();

    if (isset($updates['status'])) {
      app(BotNotifier::class)->orderStatusChanged($order);
    }
  }
}
