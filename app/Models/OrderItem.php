<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne d'article dans une commande (snapshot au moment de l'achat).
 */
class OrderItem extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_id',
    'product_id',
    'product_variant_id',
    'product_name',
    'variant_name',
    'sku',
    'quantity',
    'unit_price',
    'total_price',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'quantity' => 'integer',
      'unit_price' => 'decimal:2',
      'total_price' => 'decimal:2',
    ];
  }

  /**
   * Commande parente.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  /**
   * Produit référencé (peut être supprimé ultérieurement).
   *
   * @return BelongsTo<Product, $this>
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }

  /**
   * Variante référencée (optionnelle).
   *
   * @return BelongsTo<ProductVariant, $this>
   */
  public function variant(): BelongsTo
  {
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
  }
}
