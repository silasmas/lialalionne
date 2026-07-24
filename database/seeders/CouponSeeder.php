<?php

namespace Database\Seeders;

use App\Enums\CouponType;
use App\Models\Coupon;
use Illuminate\Database\Seeder;

/**
 * Codes promo de démonstration.
 */
class CouponSeeder extends Seeder
{
  /**
   * Crée quelques codes promo d'exemple.
   *
   * @return void
   */
  public function run(): void
  {
    Coupon::query()->updateOrCreate(
      ['code' => 'BIENVENUE10'],
      [
        'name' => 'Bienvenue -10 %',
        'type' => CouponType::Percent,
        'value' => 10,
        'min_order_amount' => 20,
        'max_discount_amount' => 15,
        'max_uses' => 100,
        'max_uses_per_user' => 1,
        'is_active' => true,
        'description' => '10 % de réduction (max 15 €) dès 20 € de panier.',
        'ends_at' => now()->addMonths(6),
      ]
    );

    Coupon::query()->updateOrCreate(
      ['code' => 'LIALA5'],
      [
        'name' => 'Remise fixe 5 €',
        'type' => CouponType::Fixed,
        'value' => 5,
        'min_order_amount' => null,
        'max_discount_amount' => null,
        'max_uses' => null,
        'max_uses_per_user' => null,
        'is_active' => true,
        'description' => '5 € de réduction sur le sous-total.',
        'ends_at' => null,
      ]
    );
  }
}
