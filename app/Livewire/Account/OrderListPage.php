<?php

namespace App\Livewire\Account;

use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Liste paginée des commandes du client connecté.
 */
class OrderListPage extends Component
{
  use WithPagination;

  /**
   * Rendu de l'historique commandes.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CurrencyService $currencyService)
  {
    $orders = Auth::user()
      ->orders()
      ->withCount('items')
      ->latest()
      ->paginate(10);

    return view('livewire.account.order-list-page', [
      'orders' => $orders,
      'currencyService' => $currencyService,
    ])->layout('layouts.shopwise', [
      'title' => 'Mes commandes — Lialalionne',
    ]);
  }
}
