<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne d'article dans un panier.
 */
class CartItem extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'cart_id',
    'product_id',
    'product_variant_id',
    'quantity',
    'unit_price',
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
    ];
  }

  /**
   * Panier parent.
   *
   * @return BelongsTo<Cart, $this>
   */
  public function cart(): BelongsTo
  {
    return $this->belongsTo(Cart::class);
  }

  /**
   * Produit associé.
   *
   * @return BelongsTo<Product, $this>
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }

  /**
   * Variante associée (optionnelle).
   *
   * @return BelongsTo<ProductVariant, $this>
   */
  public function variant(): BelongsTo
  {
    return $this->belongsTo(ProductVariant::class, 'product_variant_id');
  }

  /**
   * Calcule le total de la ligne (prix × quantité).
   *
   * @return float Montant de la ligne
   */
  public function lineTotal(): float
  {
    return (float) ($this->unit_price * $this->quantity);
  }
}
