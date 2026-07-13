<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Services\FavoriteService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Bouton favori sur fiche produit ou carte catalogue.
 */
class FavoriteButton extends Component
{
  public Product $product;

  public bool $isFavorite = false;

  /**
   * Initialise l'état favori pour l'utilisateur connecté.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return void
   */
  public function mount(FavoriteService $favoriteService): void
  {
    if (Auth::check()) {
      $this->isFavorite = $favoriteService->isFavorite(Auth::user(), $this->product);
    }
  }

  /**
   * Bascule le favori ou redirige vers la connexion.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return mixed Redirection ou mise à jour UI
   */
  public function toggle(FavoriteService $favoriteService)
  {
    if (!Auth::check()) {
      return $this->redirect(route('account.login'), navigate: true);
    }

    $this->isFavorite = $favoriteService->toggle(Auth::user(), $this->product);
  }

  /**
   * Rendu du bouton favori.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    return view('livewire.shop.favorite-button');
  }
}
