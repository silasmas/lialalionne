<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Catégorie de produits (peau, fessier, visage, etc.).
 */
class Category extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'slug',
    'description',
    'image',
    'is_active',
    'sort_order',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'is_active' => 'boolean',
      'sort_order' => 'integer',
    ];
  }

  /**
   * Produits rattachés à cette catégorie.
   *
   * @return HasMany<Product, $this>
   */
  public function products(): HasMany
  {
    return $this->hasMany(Product::class);
  }
}
