<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Image associée à un produit.
 */
class ProductImage extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'product_id',
    'path',
    'alt_text',
    'sort_order',
    'is_primary',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'sort_order' => 'integer',
      'is_primary' => 'boolean',
    ];
  }

  /**
   * Produit lié à l'image.
   *
   * @return BelongsTo<Product, $this>
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }
}
