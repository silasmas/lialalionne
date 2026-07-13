<?php

namespace App\Livewire\Shop;

use App\Livewire\Shop\Concerns\InteractsWithProductCard;
use App\Models\Product;
use App\Services\FavoriteService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Composant Livewire de la page d'accueil boutique (produits vedettes).
 */
class HomePage extends Component
{
  use InteractsWithProductCard;

  /**
   * Requête de base pour les produits actifs affichés sur l'accueil.
   *
   * @return Builder<Product> Requête Eloquent
   */
  private function activeProductsQuery(): Builder
  {
    return Product::query()
      ->where('is_active', true)
      ->with(['category', 'images', 'variants']);
  }

  /**
   * Retourne des produits avec repli sur le catalogue si la liste est vide.
   *
   * @param Builder<Product> $query Requête filtrée
   * @param int $limit Nombre maximum de produits
   * @return Collection<int, Product> Produits à afficher
   */
  private function productsOrFallback(Builder $query, int $limit = 8): Collection
  {
    $products = $query->limit($limit)->get();

    if ($products->isNotEmpty()) {
      return $products;
    }

    return $this->activeProductsQuery()
      ->orderByDesc('is_featured')
      ->orderBy('name')
      ->limit($limit)
      ->get();
  }

  /**
   * Rendu de la page d'accueil avec les produits mis en avant.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(FavoriteService $favoriteService)
  {
    $this->loadFavoriteIds($favoriteService);

    $featuredProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->where('is_featured', true)->orderBy('name')
    );

    $newArrivalProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->orderByDesc('created_at')
    );

    $bestSellerProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->orderByDesc('is_featured')->orderByDesc('created_at')
    );

    $featuredTabProducts = $featuredProducts;

    $specialOfferProducts = $this->productsOrFallback(
      $this->activeProductsQuery()
        ->whereNotNull('compare_at_price')
        ->whereColumn('compare_at_price', '>', 'price')
        ->orderByDesc('created_at')
    );

    $templateImages = collect(range(1, 8))
      ->map(fn (int $index): string => asset('shopwise/assets/images/product_img' . $index . '.jpg'))
      ->all();

    return view('livewire.shop.home-page', [
      'featuredProducts' => $featuredProducts,
      'newArrivalProducts' => $newArrivalProducts,
      'bestSellerProducts' => $bestSellerProducts,
      'featuredTabProducts' => $featuredTabProducts,
      'specialOfferProducts' => $specialOfferProducts,
      'templateImages' => $templateImages,
    ])->layout('layouts.shopwise', [
      'title' => 'Lialalionne — Soins corporels premium',
    ]);
  }
}
