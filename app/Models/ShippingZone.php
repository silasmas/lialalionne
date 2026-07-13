<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Zone géographique de livraison (pays, régions).
 */
class ShippingZone extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'name',
    'countries',
    'regions',
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
      'countries' => 'array',
      'regions' => 'array',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Tarifs applicables dans cette zone.
   *
   * @return HasMany<ShippingRate, $this>
   */
  public function rates(): HasMany
  {
    return $this->hasMany(ShippingRate::class);
  }
}
