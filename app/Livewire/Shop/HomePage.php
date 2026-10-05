<?php

namespace App\Livewire\Shop;

use App\Enums\SlideMediaType;
use App\Http\Middleware\RedirectIfComingSoon;
use App\Livewire\Shop\Concerns\InteractsWithProductCard;
use App\Models\HomeSlide;
use App\Models\Product;
use App\Services\FavoriteService;
use App\Services\SiteSettingsService;
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
      ->with(['category', 'images', 'variants'])
      ->withAvg('approvedReviews as reviews_avg_rating', 'rating')
      ->withCount('approvedReviews as reviews_count');
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
   * Slides d'accueil publiés, avec repli sur les bannières photo historiques.
   *
   * @return Collection<int, HomeSlide> Slides à afficher
   */
  private function homeSlides(): Collection
  {
    try {
      $slides = HomeSlide::query()->published()->get();
    } catch (\Throwable) {
      $slides = collect();
    }

    if ($slides->isNotEmpty()) {
      return $slides;
    }

    return $this->fallbackHomeSlides();
  }

  /**
   * Bannières photo par défaut si aucun slide n'est configuré en admin.
   *
   * @return Collection<int, HomeSlide> Slides de repli (non persistés)
   */
  private function fallbackHomeSlides(): Collection
  {
    return collect(HomeSlide::defaultCatalog())->map(function (array $slide): HomeSlide {
      return HomeSlide::make([
        'media_type' => SlideMediaType::Image,
        'image_path' => $slide['image_path'],
        'kicker' => $slide['kicker'],
        'title' => $slide['title'],
        'button_label' => $slide['button_label'],
        'button_url' => $slide['button_url'],
        'is_active' => true,
      ]);
    });
  }

  /**
   * Rendu de la page d'accueil avec les produits mis en avant.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(FavoriteService $favoriteService, SiteSettingsService $settings)
  {
    try {
      if ($settings->isComingSoonEnabled() && !session(RedirectIfComingSoon::BYPASS_SESSION_KEY)) {
        return view('livewire.shop.home-coming-soon-wrapper')
          ->layout('layouts.minimal', [
            'title' => $settings->comingSoonTitle(),
          ]);
      }
    } catch (\Throwable) {
      //
    }

    $this->loadFavoriteIds($favoriteService);

    $homeSlides = $this->homeSlides();

    $featuredProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->where('is_featured', true)->orderBy('name')
    );

    $newArrivalProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->newArrivals()
    );

    $bestSellerProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->orderByDesc('is_featured')->orderByDesc('created_at')
    );

    $featuredTabProducts = $featuredProducts;

    $specialOfferProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->specialOffers()
    );

    $seasonalProducts = $this->productsOrFallback(
      $this->activeProductsQuery()->seasonal()
    );

    $newArrivalBanner = $newArrivalProducts->first();
    $specialOfferBanner = $specialOfferProducts->first();
    $seasonalHighlight = $seasonalProducts->first();

    return view('livewire.shop.home-page', [
      'homeSlides' => $homeSlides,
      'featuredProducts' => $featuredProducts,
      'newArrivalProducts' => $newArrivalProducts,
      'bestSellerProducts' => $bestSellerProducts,
      'featuredTabProducts' => $featuredTabProducts,
      'specialOfferProducts' => $specialOfferProducts,
      'seasonalProducts' => $seasonalProducts,
      'newArrivalBanner' => $newArrivalBanner,
      'specialOfferBanner' => $specialOfferBanner,
      'seasonalHighlight' => $seasonalHighlight,
      'specialOfferDiscountPercent' => Product::maxDiscountPercent($specialOfferProducts),
    ])->layout('layouts.shopwise', [
      'title' => 'Lialalionne — Soins corporels premium en RDC',
      'metaDescription' => 'Lialalionne : cosmétiques et soins corporels naturels (fessier, ventre plat, corps) livrés à Kinshasa et partout en RDC. Paiement Mobile Money et carte.',
      'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Lialalionne',
        'url' => url('/'),
        'logo' => asset('assets/favicon-192.png'),
        'description' => 'Boutique en ligne de soins corporels premium (fessier, ventre plat, corps) en République Démocratique du Congo.',
      ],
    ]);
  }
}
