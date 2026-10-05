<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Avis client sur un produit (modéré avant publication).
 */
class Review extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'product_id',
    'user_id',
    'rating',
    'title',
    'comment',
    'is_verified_purchase',
    'is_approved',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'rating' => 'integer',
      'is_verified_purchase' => 'boolean',
      'is_approved' => 'boolean',
    ];
  }

  /**
   * Produit concerné par l'avis.
   *
   * @return BelongsTo<Product, $this>
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }

  /**
   * Auteur de l'avis.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Ne garde que les avis approuvés (visibles publiquement).
   *
   * @param Builder<Review> $query Requête Eloquent
   * @return Builder<Review> Requête filtrée
   */
  public function scopeApproved(Builder $query): Builder
  {
    return $query->where('is_approved', true);
  }
}
