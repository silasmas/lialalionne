<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Variante d'un produit (ex. 50 ml, 100 ml).
 */
class ProductVariant extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'product_id',
    'name',
    'sku',
    'price',
    'stock',
    'is_active',
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
      'stock' => 'integer',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Produit parent de la variante.
   *
   * @return BelongsTo<Product, $this>
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }
}
