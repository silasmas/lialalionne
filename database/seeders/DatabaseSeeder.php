<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Point d'entrée principal — peuple toutes les tables métier.
 */
class DatabaseSeeder extends Seeder
{
  /**
   * Exécute les seeders dans l'ordre des dépendances.
   *
   * @return void
   */
  public function run(): void
  {
    $this->call([
      SettingSeeder::class,
      UserSeeder::class,
      CategorySeeder::class,
      ProductSeeder::class,
      ShippingSeeder::class,
      CouponSeeder::class,
      OrderSeeder::class,
      CartSeeder::class,
    ]);
  }
}
