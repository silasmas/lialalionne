<?php

namespace App\Models;

use App\Services\CurrencyService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Produit vendu sur la boutique (soins corporels).
 */
class Product extends Model
{
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
    ];
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
