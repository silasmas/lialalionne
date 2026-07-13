<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché lorsqu'un numéro de suivi est renseigné pour une commande.
 */
class OrderShipped
{
  use Dispatchable, SerializesModels;

  /**
   * @param Order $order Commande expédiée
   */
  public function __construct(
    public readonly Order $order
  ) {
  }
}
