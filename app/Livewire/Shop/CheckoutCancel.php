<?php

namespace App\Livewire\Shop;

use App\Models\Order;
use Livewire\Component;

/**
 * Page affichée lorsque le client annule le paiement Stripe.
 */
class CheckoutCancel extends Component
{
  public ?Order $order = null;

  /**
   * Charge la commande annulée si le numéro est fourni.
   *
   * @param string|null $order Numéro de commande
   * @return void
   */
  public function mount(?string $order = null): void
  {
    if ($order) {
      $this->order = Order::query()
        ->where('order_number', $order)
        ->first();
    }
  }

  /**
   * Rendu de la page annulation paiement.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    return view('livewire.shop.checkout-cancel')->layout('layouts.shop', [
      'title' => 'Paiement annulé — Lialalionne',
    ]);
  }
}
