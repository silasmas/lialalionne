<?php

namespace App\Livewire\Account;

use App\Livewire\Shop\Concerns\InteractsWithProductCard;
use App\Services\FavoriteService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Page listant les produits favoris du client connecté.
 */
class FavoritesPage extends Component
{
  use InteractsWithProductCard;

  /**
   * Rendu de la liste des favoris.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(FavoriteService $favoriteService)
  {
    $this->loadFavoriteIds($favoriteService);

    $products = $favoriteService->listProducts(Auth::user());

    return view('livewire.account.favorites-page', [
      'products' => $products,
    ])->layout('layouts.shopwise', [
      'title' => 'Mes favoris — Lialalionne',
    ]);
  }
}
