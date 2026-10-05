<?php

namespace App\Models;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Laravel\Scout\Attributes\SearchUsingFullText;
use Laravel\Scout\Searchable;

/**
 * Produit vendu sur la boutique (soins corporels).
 */
class Product extends Model
{
  use Searchable;

  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'category_id',
    'name',
    'slug',
    'sku',
    'short_description',
    'description',
    'ingredients',
    'usage_tips',
    'price',
    'compare_at_price',
    'stock',
    'track_stock',
    'is_active',
    'is_featured',
    'is_new',
    'is_seasonal',
    'weight',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'price' => 'decimal:2',
      'compare_at_price' => 'decimal:2',
      'weight' => 'decimal:2',
      'stock' => 'integer',
      'track_stock' => 'boolean',
      'is_active' => 'boolean',
      'is_featured' => 'boolean',
      'is_new' => 'boolean',
      'is_seasonal' => 'boolean',
    ];
  }

  /**
   * Produits liés pour une vente en gamme (côté source).
   *
   * @return BelongsToMany<Product, $this>
   */
  public function relatedProducts(): BelongsToMany
  {
    return $this->belongsToMany(self::class, 'product_related', 'product_id', 'related_product_id')
      ->withTimestamps();
  }

  /**
   * Produits qui pointent vers celui-ci dans une gamme.
   *
   * @return BelongsToMany<Product, $this>
   */
  public function inverseRelatedProducts(): BelongsToMany
  {
    return $this->belongsToMany(self::class, 'product_related', 'related_product_id', 'product_id')
      ->withTimestamps();
  }

  /**
   * Autres produits de la même gamme, vendables ensemble ou séparément.
   *
   * @return Collection<int, Product> Produits actifs liés
   */
  public function rangeCompanions(): Collection
  {
    $this->loadMissing([
      'relatedProducts.images',
      'relatedProducts.variants',
      'inverseRelatedProducts.images',
      'inverseRelatedProducts.variants',
    ]);

    return $this->relatedProducts
      ->merge($this->inverseRelatedProducts)
      ->unique('id')
      ->filter(fn (Product $product): bool => $product->is_active && $product->id !== $this->id)
      ->values();
  }

  /**
   * Synchronise une gamme dans les deux sens (A↔B).
   *
   * @param list<int> $relatedIds Identifiants des produits liés
   * @return void
   */
  public function syncRangeCompanions(array $relatedIds): void
  {
    $relatedIds = array_values(array_unique(array_map('intval', $relatedIds)));
    $relatedIds = array_values(array_filter($relatedIds, fn (int $id): bool => $id !== (int) $this->id));

    $previousIds = $this->relatedProducts()->pluck('products.id')
      ->merge($this->inverseRelatedProducts()->pluck('products.id'))
      ->unique()
      ->all();

    $this->relatedProducts()->sync($relatedIds);

    foreach (array_diff($previousIds, $relatedIds) as $removedId) {
      static::query()->find($removedId)?->relatedProducts()->detach($this->id);
    }

    foreach ($relatedIds as $relatedId) {
      static::query()->find($relatedId)?->relatedProducts()->syncWithoutDetaching([$this->id]);
    }
  }

  /**
   * Produits actifs visibles sur la boutique.
   *
   * @param Builder<Product> $query Requête
   * @return Builder<Product> Requête filtrée
   */
  public function scopeActive(Builder $query): Builder
  {
    return $query->where('is_active', true);
  }

  /**
   * Nouveautés / nouvelle collection.
   *
   * @param Builder<Product> $query Requête
   * @return Builder<Product> Requête filtrée
   */
  public function scopeNewArrivals(Builder $query): Builder
  {
    return $query->where('is_new', true)->orderByDesc('created_at');
  }

  /**
   * Offres spéciales (prix barré supérieur au prix actuel).
   *
   * @param Builder<Product> $query Requête
   * @return Builder<Product> Requête filtrée
   */
  public function scopeSpecialOffers(Builder $query): Builder
  {
    return $query
      ->whereNotNull('compare_at_price')
      ->whereColumn('compare_at_price', '>', 'price')
      ->orderByDesc('created_at');
  }

  /**
   * Tendances de saison.
   *
   * @param Builder<Product> $query Requête
   * @return Builder<Product> Requête filtrée
   */
  public function scopeSeasonal(Builder $query): Builder
  {
    return $query->where('is_seasonal', true)->orderByDesc('is_featured')->orderBy('name');
  }

  /**
   * Plus forte remise (en %) parmi une collection de produits.
   *
   * @param Collection<int, Product>|iterable<Product> $products Produits à analyser
   * @return int Pourcentage arrondi, 0 si aucune promo
   */
  public static function maxDiscountPercent(iterable $products): int
  {
    $maxPercent = 0;

    foreach ($products as $product) {
      if (!$product instanceof self || !$product->hasDiscount() || (float) $product->compare_at_price <= 0) {
        continue;
      }

      $percent = (int) round((1 - ((float) $product->price / (float) $product->compare_at_price)) * 100);
      $maxPercent = max($maxPercent, $percent);
    }

    return $maxPercent;
  }

  /**
   * Catégorie du produit.
   *
   * @return BelongsTo<Category, $this>
   */
  public function category(): BelongsTo
  {
    return $this->belongsTo(Category::class);
  }

  /**
   * Variantes disponibles (format, taille, etc.).
   *
   * @return HasMany<ProductVariant, $this>
   */
  public function variants(): HasMany
  {
    return $this->hasMany(ProductVariant::class);
  }

  /**
   * Images associées au produit.
   *
   * @return HasMany<ProductImage, $this>
   */
  public function images(): HasMany
  {
    return $this->hasMany(ProductImage::class)->orderBy('sort_order');
  }

  /**
   * Tous les avis du produit (approuvés ou non — usage admin).
   *
   * @return HasMany<Review, $this>
   */
  public function reviews(): HasMany
  {
    return $this->hasMany(Review::class);
  }

  /**
   * Avis approuvés et publiquement visibles.
   *
   * @return HasMany<Review, $this>
   */
  public function approvedReviews(): HasMany
  {
    return $this->hasMany(Review::class)->where('is_approved', true);
  }

  /**
   * Note moyenne (0 à 5) parmi les avis approuvés.
   * Utilise l'agrégat pré-chargé (withAvg) si disponible, sinon calcule.
   *
   * @return float Note moyenne, 0 si aucun avis
   */
  public function averageRating(): float
  {
    if (array_key_exists('reviews_avg_rating', $this->attributes)) {
      return round((float) ($this->attributes['reviews_avg_rating'] ?? 0), 1);
    }

    return round((float) $this->approvedReviews()->avg('rating'), 1);
  }

  /**
   * Nombre d'avis approuvés.
   * Utilise l'agrégat pré-chargé (withCount) si disponible, sinon calcule.
   *
   * @return int Nombre d'avis
   */
  public function reviewsCount(): int
  {
    if (array_key_exists('reviews_count', $this->attributes)) {
      return (int) $this->attributes['reviews_count'];
    }

    return $this->approvedReviews()->count();
  }

  /** Nombre max d'images par produit (1 principale + 5 illustrations). */
  public const MAX_IMAGES = 6;

  /** Nombre max d'images d'illustration (hors principale). */
  public const MAX_ILLUSTRATION_IMAGES = 5;

  /**
   * Clients ayant mis ce produit en favori.
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<User, $this>
   */
  public function favoritedByUsers(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
  {
    return $this->belongsToMany(User::class, 'product_favorites')
      ->withTimestamps();
  }

  /**
   * Indique si le produit est en stock.
   *
   * @return bool True si disponible à la vente
   */
  public function isInStock(): bool
  {
    if (!$this->track_stock) {
      return true;
    }

    return $this->stock > 0;
  }

  /**
   * Clé de route pour les URLs SEO (slug).
   *
   * @return string Nom de la colonne
   */
  public function getRouteKeyName(): string
  {
    return 'slug';
  }

  /**
   * Champs indexés pour la recherche (Scout, moteur "database" avec
   * correspondance plein texte MySQL sur nom + description courte).
   *
   * @return array<string, mixed>
   */
  #[SearchUsingFullText(['name', 'short_description'])]
  public function toSearchableArray(): array
  {
    return [
      'name' => $this->name,
      'short_description' => $this->short_description,
      'sku' => $this->sku,
    ];
  }

  /**
   * Seuls les produits actifs sont indexés pour la recherche.
   *
   * @return bool True si indexable
   */
  public function shouldBeSearchable(): bool
  {
    return $this->is_active;
  }

  /**
   * URL de l'image principale ou null si aucune image.
   *
   * @return string|null URL publique de l'image
   */
  public function primaryImageUrl(): ?string
  {
    $image = $this->images->firstWhere('is_primary', true)
      ?? $this->images->sortBy('sort_order')->first();

    return $image?->url;
  }

  /**
   * Formate le prix dans la devise active (CDF ou USD).
   *
   * @param float|string|null $price Prix à formater (défaut : prix produit)
   * @return string Prix formaté
   */
  public function formatPrice(float|string|null $price = null): string
  {
    $amount = (float) ($price ?? $this->price);

    return app(CurrencyService::class)->formatFromEur($amount);
  }

  /**
   * Indique si le produit est en promotion (prix barré).
   *
   * @return bool True si compare_at_price > price
   */
  public function hasDiscount(): bool
  {
    return $this->compare_at_price !== null
      && (float) $this->compare_at_price > (float) $this->price;
  }
}
