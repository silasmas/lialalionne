<?php

namespace App\Livewire\Shop;

use App\Models\Order;
use App\Services\CurrencyService;
use App\Services\PaymentService;
use Livewire\Component;

/**
 * Page de confirmation de commande après paiement réussi.
 */
class OrderConfirmation extends Component
{
  public ?Order $order = null;

  /**
   * Vérifie le paiement et charge la commande.
   *
   * @param PaymentService $paymentService Service paiement
   * @return void
   */
  public function mount(PaymentService $paymentService): void
  {
    $sessionId = request()->query('session_id');

    if (!$sessionId) {
      abort(404);
    }

    try {
      $this->order = $paymentService->confirmCheckoutSession($sessionId);
      $this->order->load(['items', 'shippingAddress', 'payment']);
    } catch (\Throwable) {
      $this->order = Order::query()
        ->whereHas('payment', fn ($q) => $q->where('transaction_id', $sessionId))
        ->with(['items', 'shippingAddress', 'payment'])
        ->first();

      if (!$this->order) {
        abort(404);
      }
    }
  }

  /**
   * Rendu de la page confirmation.
   *
   * @param CurrencyService $currencyService Service devises
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CurrencyService $currencyService)
  {
    return view('livewire.shop.order-confirmation', [
      'currencyService' => $currencyService,
    ])->layout('layouts.shopwise', [
      'title' => 'Commande confirmée — Lialalionne',
    ]);
  }
}
