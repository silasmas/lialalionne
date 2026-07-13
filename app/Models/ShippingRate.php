<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Tarif de livraison pour une zone et un montant de commande donné.
 */
class ShippingRate extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'shipping_zone_id',
    'name',
    'min_order_amount',
    'max_order_amount',
    'price',
    'estimated_days_min',
    'estimated_days_max',
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
      'min_order_amount' => 'decimal:2',
      'max_order_amount' => 'decimal:2',
      'price' => 'decimal:2',
      'estimated_days_min' => 'integer',
      'estimated_days_max' => 'integer',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Zone de livraison parente.
   *
   * @return BelongsTo<ShippingZone, $this>
   */
  public function zone(): BelongsTo
  {
    return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
  }
}
