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

  #[Url(as: 'vue', history: true, keep: true)]
  public string $viewMode = 'grid';

  #[Url(as: 'selection')]
  public string $selection = '';

  /**
   * Restaure le mode d'affichage depuis la session si besoin.
   *
   * @return void
   */
  public function mount(): void
  {
    if (!request()->has('vue') && session()->has('shop_view_mode')) {
      $this->viewMode = $this->normalizeViewMode((string) session('shop_view_mode'));
    } else {
      $this->viewMode = $this->normalizeViewMode($this->viewMode);
    }

    session(['shop_view_mode' => $this->viewMode]);
  }

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
   * Persiste le mode d'affichage quand il change.
   *
   * @param string $value Mode grille ou liste
   * @return void
   */
  public function updatedViewMode(string $value): void
  {
    $this->viewMode = $this->normalizeViewMode($value);
    session(['shop_view_mode' => $this->viewMode]);
  }

  /**
   * Efface tous les filtres actifs (conserve la vue grille/liste).
   *
   * @return void
   */
  public function resetFilters(): void
  {
    $this->search = '';
    $this->categoryId = null;
    $this->sort = 'featured';
    $this->selection = '';
    $this->resetPage();
  }

  /**
   * Réinitialise la pagination quand la sélection merchandising change.
   *
   * @return void
   */
  public function updatedSelection(): void
  {
    $this->selection = $this->normalizeSelection($this->selection);
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
    session(['shop_view_mode' => 'grid']);
  }

  /**
   * Active l'affichage en liste.
   *
   * @return void
   */
  public function setListView(): void
  {
    $this->viewMode = 'list';
    session(['shop_view_mode' => 'list']);
  }

  /**
   * Normalise le mode d'affichage catalogue.
   *
   * @param string $mode Mode demandé
   * @return string Mode valide (grid|list)
   */
  private function normalizeViewMode(string $mode): string
  {
    return in_array($mode, ['grid', 'list'], true) ? $mode : 'grid';
  }

  /**
   * Normalise le filtre merchandising (nouveautés, offres, tendances).
   *
   * @param string $selection Filtre demandé
   * @return string Filtre valide ou chaîne vide
   */
  private function normalizeSelection(string $selection): string
  {
    return in_array($selection, ['nouveautes', 'offres', 'tendances', 'collection'], true)
      ? $selection
      : '';
  }

  /**
   * Applique le filtre merchandising sur une requête catalogue.
   *
   * @param \Illuminate\Database\Eloquent\Builder<Product> $query Requête produits
   * @return void
   */
  private function applySelectionFilter($query): void
  {
    match ($this->normalizeSelection($this->selection)) {
      'nouveautes', 'collection' => $query->where('is_new', true),
      'offres' => $query->whereNotNull('compare_at_price')->whereColumn('compare_at_price', '>', 'price'),
      'tendances' => $query->where('is_seasonal', true),
      default => null,
    };
  }

  /**
   * Construit la requête produits filtrée et triée.
   *
   * @return \Illuminate\Database\Eloquent\Builder<Product>
   */
  private function buildQuery()
  {
    $categoryId = $this->categoryId;

    if ($this->search !== '') {
      // Recherche structurée (Scout, moteur "database") : correspondance
      // plein texte sur nom/description + préfixe sur le SKU. Les mêmes
      // filtres et eager-loads que la navigation classique sont injectés via
      // query() pour garder un comportement identique en dehors du texte.
      $query = Product::search($this->search)->query(function ($builder) use ($categoryId) {
        $builder
          ->where('is_active', true)
          ->with(['category', 'images', 'variants'])
          ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
          ->withCount('approvedReviews as reviews_count');

        if ($categoryId) {
          $builder->where('category_id', $categoryId);
        }

        $this->applySelectionFilter($builder);
      });
    } else {
      $query = Product::query()
        ->where('is_active', true)
        ->with(['category', 'images', 'variants'])
        ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
        ->withCount('approvedReviews as reviews_count');

      if ($categoryId) {
        $query->where('category_id', $categoryId);
      }

      $this->applySelectionFilter($query);
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

    $products = $this->buildQuery()->paginate(20);
    $categories = Category::query()
      ->where('is_active', true)
      ->orderBy('sort_order')
      ->orderBy('name')
      ->withCount(['products' => fn ($q) => $q->where('is_active', true)])
      ->get();

    $activeCategory = $this->categoryId
      ? $categories->firstWhere('id', $this->categoryId)
      : null;

    $selection = $this->normalizeSelection($this->selection);

    $newCollectionProducts = Product::query()
      ->active()
      ->newArrivals()
      ->with('images')
      ->limit(8)
      ->get();

    $newCollectionBanner = $newCollectionProducts->first();
    $newCollectionDiscountPercent = Product::maxDiscountPercent($newCollectionProducts);

    $title = match (true) {
      $this->search !== '' => "Recherche « {$this->search} » — Boutique Lialalionne",
      $selection === 'nouveautes', $selection === 'collection' => 'Nouvelle collection — Boutique Lialalionne',
      $selection === 'offres' => 'Offres spéciales — Boutique Lialalionne',
      $selection === 'tendances' => 'Tendances de saison — Boutique Lialalionne',
      $activeCategory !== null => $activeCategory->name . ' — Boutique Lialalionne',
      default => 'Boutique — Lialalionne',
    };

    $description = $activeCategory?->description
      ?? 'Découvrez tous les soins corporels Lialalionne : fessier, ventre plat, corps. Livraison à Kinshasa et en RDC, paiement Mobile Money.';

    return view('livewire.shop.product-catalog', [
      'products' => $products,
      'categories' => $categories,
      'newCollectionBanner' => $newCollectionBanner,
      'newCollectionDiscountPercent' => $newCollectionDiscountPercent,
    ])->layout('layouts.shopwise', [
      'title' => $title,
      'metaDescription' => \Illuminate\Support\Str::limit($description, 160),
    ]);
  }
}
