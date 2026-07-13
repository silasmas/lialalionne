<?php

namespace App\Observers;

use App\Events\OrderShipped;
use App\Models\Order;

/**
 * Observer des commandes : déclenche les notifications métier à la mise à jour.
 */
class OrderObserver
{
  /**
   * Déclenche l'email d'expédition quand un numéro de suivi est renseigné.
   *
   * @param Order $order Commande mise à jour
   * @return void
   */
  public function updated(Order $order): void
  {
    if (!$order->wasChanged('tracking_number')) {
      return;
    }

    $tracking = trim((string) $order->tracking_number);

    if ($tracking === '') {
      return;
    }

    if ($order->shipment_notified_tracking === $tracking) {
      return;
    }

    OrderShipped::dispatch($order);
  }
}
