<?php

namespace App\View\Composers;

use App\Models\Category;
use Illuminate\View\View;

/**
 * Injecte les données communes du thème Shopwise (menu, footer, helper assets).
 */
class ShopwiseComposer
{
  /**
   * Partage les catégories et helpers avec les vues Shopwise.
   *
   * @param View $view Vue Blade en cours de rendu
   * @return void
   */
  public function compose(View $view): void
  {
    $view->with('sw', fn (string $path): string => asset('shopwise/assets/' . ltrim($path, '/')));

    $view->with('shopwiseCategories', Category::query()
      ->where('is_active', true)
      ->orderBy('sort_order')
      ->orderBy('name')
      ->with(['products' => fn ($query) => $query
        ->where('is_active', true)
        ->orderByDesc('is_featured')
        ->orderBy('name')
        ->limit(5)])
      ->get());

    $view->with('footerCategories', Category::query()
      ->where('is_active', true)
      ->orderBy('sort_order')
      ->orderBy('name')
      ->limit(5)
      ->get());
  }
}
