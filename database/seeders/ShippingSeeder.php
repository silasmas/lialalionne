<?php

namespace Database\Seeders;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Database\Seeder;

/**
 * Peuple les zones et tarifs de livraison.
 */
class ShippingSeeder extends Seeder
{
  /**
   * Crée les zones géographiques et leurs tarifs.
   *
   * @return void
   */
  public function run(): void
  {
    $rdc = ShippingZone::query()->firstOrCreate(
      ['name' => 'RD Congo'],
      [
        'countries' => ['CD'],
        'regions' => null,
        'is_active' => true,
      ]
    );

    if (!$rdc->rates()->exists()) {
      ShippingRate::query()->create([
        'shipping_zone_id' => $rdc->id,
        'name' => 'Standard Kinshasa (2–4 jours)',
        'min_order_amount' => 0,
        'max_order_amount' => null,
        'price' => 4.50,
        'estimated_days_min' => 2,
        'estimated_days_max' => 4,
        'is_active' => true,
      ]);

      ShippingRate::query()->create([
        'shipping_zone_id' => $rdc->id,
        'name' => 'Express (24 h)',
        'min_order_amount' => 0,
        'max_order_amount' => null,
        'price' => 7.50,
        'estimated_days_min' => 1,
        'estimated_days_max' => 1,
        'is_active' => true,
      ]);
    }
  }
}
