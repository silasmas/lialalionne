<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use Illuminate\Support\Collection;
use Livewire\Component;

/**
 * Modale d'accueil présentant jusqu'à 5 produits vedettes (avec image) en diaporama.
 */
class FeaturedWelcomePopup extends Component
{
  /**
   * Charge aléatoirement jusqu'à 5 produits vedettes actifs ayant au moins une image.
   *
   * @return Collection<int, Product> Produits à afficher
   */
  public function featuredProducts(): Collection
  {
    return Product::query()
      ->where('is_active', true)
      ->where('is_featured', true)
      ->whereHas('images')
      ->with(['images', 'category'])
      ->inRandomOrder()
      ->limit(5)
      ->get();
  }

  /**
   * Rendu de la modale d'accueil.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    return view('livewire.shop.featured-welcome-popup', [
      'products' => $this->featuredProducts(),
    ]);
  }
}
