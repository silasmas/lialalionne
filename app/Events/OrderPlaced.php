<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Événement déclenché lorsqu'une commande est créée avec succès.
 */
class OrderPlaced
{
  use Dispatchable, SerializesModels;

  /**
   * @param Order $order Commande nouvellement créée
   */
  public function __construct(
    public readonly Order $order
  ) {
  }
}
