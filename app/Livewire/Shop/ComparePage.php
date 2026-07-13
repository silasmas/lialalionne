<?php

namespace App\Livewire\Shop;

use App\Livewire\Shop\Concerns\InteractsWithProductCard;
use App\Models\Product;
use App\Services\CompareService;
use App\Services\FavoriteService;
use Livewire\Component;

/**
 * Page comparaison produits (template Shopwise).
 */
class ComparePage extends Component
{
  use InteractsWithProductCard;

  /**
   * Retire un produit de la comparaison.
   *
   * @param int $productId Identifiant produit
   * @param CompareService $compareService Service comparaison
   * @return void
   */
  public function removeFromCompare(int $productId, CompareService $compareService): void
  {
    $compareService->remove($productId);
  }

  /**
   * Vide toute la liste de comparaison.
   *
   * @param CompareService $compareService Service comparaison
   * @return void
   */
  public function clearCompare(CompareService $compareService): void
  {
    $compareService->clear();
  }

  /**
   * Rendu de la page comparaison.
   *
   * @param CompareService $compareService Service comparaison
   * @param FavoriteService $favoriteService Service favoris
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CompareService $compareService, FavoriteService $favoriteService)
  {
    $this->loadFavoriteIds($favoriteService);

    return view('livewire.shop.compare-page', [
      'compareProducts' => $compareService->products(),
    ])->layout('layouts.shopwise', [
      'title' => 'Comparer — Lialalionne',
    ]);
  }
}
