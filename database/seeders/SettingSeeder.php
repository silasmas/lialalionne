<?php

namespace Database\Seeders;

use App\Enums\AuthMode;
use App\Services\SiteSettingsService;
use Illuminate\Database\Seeder;

/**
 * Initialise les paramètres boutique par défaut.
 */
class SettingSeeder extends Seeder
{
  /**
   * Enregistre auth, paiements FlexPay, devises et retrait boutique.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @return void
   */
  public function run(SiteSettingsService $settings): void
  {
    $settings->setMany([
      'auth_mode' => AuthMode::EmailOtp->value,
      'payment_card_enabled' => true,
      'payment_mobile_money_enabled' => true,
      'currency_mode' => 'dual',
      'currency_primary' => 'CDF',
      'currency_secondary' => 'USD',
      'rate_eur_cdf' => 2850,
      'rate_eur_usd' => 1.08,
      'pickup_in_store_enabled' => true,
      'pickup_store_name' => 'Boutique Lialalionne',
      'pickup_store_address' => 'Kinshasa, RDC — retrait du lundi au samedi.',
      'coming_soon_enabled' => false,
      'coming_soon_title' => 'Lialalionne arrive bientôt',
      'coming_soon_message' => 'Notre boutique en ligne de soins corporels premium ouvre très prochainement.',
      'coming_soon_launch_at' => null,
      'coming_soon_bypass_secret' => null,
    ]);
  }
}
