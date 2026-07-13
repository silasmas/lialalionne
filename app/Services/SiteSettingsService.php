<?php

namespace App\Services;

use App\Enums\AuthMode;
use App\Enums\PaymentMethod;
use App\Models\Setting;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;

/**
 * Service de lecture/écriture des paramètres boutique (auth, paiements).
 */
class SiteSettingsService
{
  private const CACHE_KEY = 'site_settings.all';

  /**
   * Valeurs par défaut si absentes en base.
   *
   * @var array<string, mixed>
   */
  private const DEFAULTS = [
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
    'pickup_store_address' => 'Kinshasa, RDC — retrait sur place du lundi au samedi.',
    'coming_soon_enabled' => false,
    'coming_soon_title' => 'Lialalionne arrive bientôt',
    'coming_soon_message' => 'Notre boutique en ligne de soins corporels premium ouvre très prochainement. La puissance d\'une lionne, la peau d\'une reine.',
    'coming_soon_launch_at' => null,
    'coming_soon_bypass_secret' => null,
  ];

  /**
   * Retourne tous les paramètres fusionnés avec les défauts.
   *
   * @return array<string, mixed> Paramètres applicatifs
   */
  public function all(): array
  {
    try {
      if (!Schema::hasTable('settings')) {
        return self::DEFAULTS;
      }
    } catch (\Throwable) {
      return self::DEFAULTS;
    }

    return Cache::remember(self::CACHE_KEY, 3600, function (): array {
      $stored = Setting::query()
        ->get()
        ->mapWithKeys(function (Setting $setting): array {
          $value = $setting->value;

          if (is_array($value) && array_key_exists('value', $value)) {
            return [$setting->key => $value['value']];
          }

          return [$setting->key => $value];
        })
        ->toArray();

      return array_merge(self::DEFAULTS, $stored);
    });
  }

  /**
   * Lit une clé de paramètre.
   *
   * @param string $key Clé du paramètre
   * @param mixed $default Valeur par défaut
   * @return mixed Valeur du paramètre
   */
  public function get(string $key, mixed $default = null): mixed
  {
    $all = $this->all();

    return $all[$key] ?? $default;
  }

  /**
   * Enregistre plusieurs paramètres et vide le cache.
   *
   * @param array<string, mixed> $values Paramètres à persister
   * @return void
   */
  public function setMany(array $values): void
  {
    foreach ($values as $key => $value) {
      Setting::query()->updateOrCreate(
        ['key' => $key],
        ['value' => ['value' => $value]]
      );
    }

    Cache::forget(self::CACHE_KEY);
  }

  /**
   * Retourne le mode d'authentification client actif.
   *
   * @return AuthMode Mode configuré
   */
  public function authMode(): AuthMode
  {
    $value = $this->get('auth_mode', AuthMode::EmailOtp->value);

    return AuthMode::tryFrom((string) $value) ?? AuthMode::EmailOtp;
  }

  /**
   * Indique si une méthode de paiement est activée côté admin.
   *
   * @param PaymentMethod $method Méthode de paiement
   * @return bool True si activée
   */
  public function isPaymentMethodEnabled(PaymentMethod $method): bool
  {
    return match ($method) {
      PaymentMethod::Stripe => (bool) $this->get('payment_card_enabled', true),
      PaymentMethod::MobileMoney => (bool) $this->get('payment_mobile_money_enabled', true),
      default => false,
    };
  }

  /**
   * Retourne les méthodes de paiement activées pour le checkout.
   *
   * @return list<PaymentMethod> Méthodes disponibles
   */
  public function enabledPaymentMethods(): array
  {
    $methods = [];

    if ($this->isPaymentMethodEnabled(PaymentMethod::Stripe)) {
      $methods[] = PaymentMethod::Stripe;
    }

    if ($this->isPaymentMethodEnabled(PaymentMethod::MobileMoney)) {
      $methods[] = PaymentMethod::MobileMoney;
    }

    return $methods;
  }

  /**
   * Mode devises : une seule ou deux monnaies au checkout.
   *
   * @return string single ou dual
   */
  public function currencyMode(): string
  {
    $mode = (string) $this->get('currency_mode', 'dual');

    return in_array($mode, ['single', 'dual'], true) ? $mode : 'dual';
  }

  /**
   * Devise principale configurée (CDF ou USD).
   *
   * @return string Code devise
   */
  public function primaryCurrency(): string
  {
    $currency = strtoupper((string) $this->get('currency_primary', 'CDF'));

    return in_array($currency, ['CDF', 'USD'], true) ? $currency : 'CDF';
  }

  /**
   * Devise secondaire si mode dual.
   *
   * @return string Code devise
   */
  public function secondaryCurrency(): string
  {
    $currency = strtoupper((string) $this->get('currency_secondary', 'USD'));

    return in_array($currency, ['CDF', 'USD'], true) ? $currency : 'USD';
  }

  /**
   * Indique si le retrait en boutique est proposé au checkout.
   *
   * @return bool True si activé
   */
  public function isPickupEnabled(): bool
  {
    return (bool) $this->get('pickup_in_store_enabled', true);
  }

  /**
   * Indique si le mode Coming Soon est activé.
   *
   * @return bool True si la boutique publique est fermée
   */
  public function isComingSoonEnabled(): bool
  {
    return (bool) $this->get('coming_soon_enabled', false);
  }

  /**
   * Retourne le titre affiché sur la page Coming Soon.
   *
   * @return string Titre
   */
  public function comingSoonTitle(): string
  {
    return (string) $this->get('coming_soon_title', 'Lialalionne arrive bientôt');
  }

  /**
   * Retourne le message affiché sur la page Coming Soon.
   *
   * @return string Message
   */
  public function comingSoonMessage(): string
  {
    return (string) $this->get('coming_soon_message', '');
  }

  /**
   * Retourne la date de lancement affichée si renseignée.
   *
   * @return string|null Date ISO ou null
   */
  public function comingSoonLaunchAt(): ?string
  {
    $value = $this->get('coming_soon_launch_at');

    return $value ? (string) $value : null;
  }

  /**
   * Retourne le secret permettant un accès manuel à la boutique.
   *
   * @return string|null Secret ou null
   */
  public function comingSoonBypassSecret(): ?string
  {
    $secret = $this->get('coming_soon_bypass_secret');

    return $secret ? (string) $secret : null;
  }
}
