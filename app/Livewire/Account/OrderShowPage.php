<?php

namespace App\Livewire\Account;

use App\Models\Order;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Détail d'une commande client (lecture seule).
 */
class OrderShowPage extends Component
{
  public Order $order;

  /**
   * Charge la commande en vérifiant qu'elle appartient au client.
   *
   * @param Order $order Commande résolue par numéro
   * @return void
   */
  public function mount(Order $order): void
  {
    if ($order->user_id !== Auth::id()) {
      abort(403);
    }

    $order->load(['items', 'shippingAddress', 'payment']);

    $this->order = $order;
  }

  /**
   * Rendu du détail commande.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CurrencyService $currencyService)
  {
    return view('livewire.account.order-show-page', [
      'currencyService' => $currencyService,
    ])->layout('layouts.shopwise', [
      'title' => 'Commande ' . $this->order->order_number . ' — Lialalionne',
    ]);
  }
}
