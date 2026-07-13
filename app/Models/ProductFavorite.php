<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Favori produit enregistré par un client connecté.
 */
class ProductFavorite extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'user_id',
    'product_id',
  ];

  /**
   * Client ayant ajouté le favori.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Produit mis en favori.
   *
   * @return BelongsTo<Product, $this>
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }
}
