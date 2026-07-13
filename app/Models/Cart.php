<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Panier d'achat (session ou utilisateur connecté).
 */
class Cart extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'user_id',
    'session_id',
  ];

  /**
   * Client propriétaire du panier.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Articles contenus dans le panier.
   *
   * @return HasMany<CartItem, $this>
   */
  public function items(): HasMany
  {
    return $this->hasMany(CartItem::class);
  }

  /**
   * Calcule le sous-total du panier.
   *
   * @return float Montant total des articles
   */
  public function subtotal(): float
  {
    $this->loadMissing('items');

    return (float) $this->items->sum(fn (CartItem $item) => $item->lineTotal());
  }
}
