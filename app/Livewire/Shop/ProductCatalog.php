<?php

namespace App\Livewire\Shop;

use App\Livewire\Shop\Concerns\InteractsWithProductCard;
use App\Models\Category;
use App\Models\Product;
use App\Services\FavoriteService;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Catalogue produits avec recherche, filtres catégorie et tri.
 */
class ProductCatalog extends Component
{
  use InteractsWithProductCard;
  use WithPagination;

  #[Url(as: 'q')]
  public string $search = '';

  #[Url(as: 'categorie')]
  public ?int $categoryId = null;

  #[Url(as: 'tri')]
  public string $sort = 'featured';

  #[Url(as: 'vue')]
  public string $viewMode = 'grid';

  /**
   * Réinitialise la pagination quand la recherche change.
   *
   * @return void
   */
  public function updatedSearch(): void
  {
    $this->resetPage();
  }

  /**
   * Réinitialise la pagination quand la catégorie change.
   *
   * @return void
   */
  public function updatedCategoryId(): void
  {
    $this->resetPage();
  }

  /**
   * Réinitialise la pagination quand le tri change.
   *
   * @return void
   */
  public function updatedSort(): void
  {
    $this->resetPage();
  }

  /**
   * Efface tous les filtres actifs.
   *
   * @return void
   */
  public function resetFilters(): void
  {
    $this->search = '';
    $this->categoryId = null;
    $this->sort = 'featured';
    $this->resetPage();
  }

  /**
   * Active l'affichage en grille.
   *
   * @return void
   */
  public function setGridView(): void
  {
    $this->viewMode = 'grid';
  }

  /**
   * Active l'affichage en liste.
   *
   * @return void
   */
  public function setListView(): void
  {
    $this->viewMode = 'list';
  }

  /**
   * Construit la requête produits filtrée et triée.
   *
   * @return \Illuminate\Database\Eloquent\Builder<Product>
   */
  private function buildQuery()
  {
    $query = Product::query()
      ->where('is_active', true)
      ->with(['category', 'images', 'variants']);

    if ($this->search !== '') {
      $term = '%' . $this->search . '%';
      $query->where(function ($builder) use ($term) {
        $builder
          ->where('name', 'like', $term)
          ->orWhere('short_description', 'like', $term)
          ->orWhere('sku', 'like', $term);
      });
    }

    if ($this->categoryId) {
      $query->where('category_id', $this->categoryId);
    }

    return match ($this->sort) {
      'price_asc' => $query->orderBy('price'),
      'price_desc' => $query->orderByDesc('price'),
      'name' => $query->orderBy('name'),
      'newest' => $query->orderByDesc('created_at'),
      default => $query->orderByDesc('is_featured')->orderBy('name'),
    };
  }

  /**
   * Rendu du catalogue paginé.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(FavoriteService $favoriteService)
  {
    $this->loadFavoriteIds($favoriteService);

    $products = $this->buildQuery()->paginate(12);
    $categories = Category::query()
      ->where('is_active', true)
      ->orderBy('sort_order')
      ->orderBy('name')
      ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
      ->get();

    return view('livewire.shop.product-catalog', [
      'products' => $products,
      'categories' => $categories,
    ])->layout('layouts.shopwise', [
      'title' => 'Boutique — Lialalionne',
    ]);
  }
}
