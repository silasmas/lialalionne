<?php

namespace App\Services;

/**
 * Conversion et affichage multi-devises (prix catalogue → CDF / USD).
 */
class CurrencyService
{
  /**
   * @param SiteSettingsService $settings Paramètres boutique
   */
  public function __construct(
    private readonly SiteSettingsService $settings
  ) {
  }

  /**
   * Devise sélectionnée en session (ou devise par défaut).
   *
   * @return string Code ISO devise (CDF ou USD)
   */
  public function selectedCurrency(): string
  {
    $available = $this->availableCurrencies();
    $sessionCurrency = session('shop_currency');

    if ($sessionCurrency && in_array($sessionCurrency, $available, true)) {
      return $sessionCurrency;
    }

    return $this->settings->primaryCurrency();
  }

  /**
   * Enregistre la devise choisie par le client.
   *
   * @param string $currency Code devise
   * @return void
   */
  public function setSelectedCurrency(string $currency): void
  {
    if (!in_array($currency, $this->availableCurrencies(), true)) {
      return;
    }

    session(['shop_currency' => $currency]);
  }

  /**
   * Devises disponibles selon la configuration admin.
   *
   * @return list<string> Codes devises
   */
  public function availableCurrencies(): array
  {
    if ($this->settings->currencyMode() === 'dual') {
      return array_values(array_unique([
        $this->settings->primaryCurrency(),
        $this->settings->secondaryCurrency(),
      ]));
    }

    return [$this->settings->primaryCurrency()];
  }

  /**
   * Convertit un montant EUR (prix catalogue) vers la devise cible.
   *
   * @param float $amountEur Montant en EUR
   * @param string|null $toCurrency Devise cible (défaut : sélection session)
   * @return float Montant converti
   */
  public function convertFromEur(float $amountEur, ?string $toCurrency = null): float
  {
    $currency = $toCurrency ?? $this->selectedCurrency();

    if ($currency === 'EUR') {
      return round($amountEur, 2);
    }

    $rate = $this->getRateFromEur($currency);

    return round($amountEur * $rate, $currency === 'CDF' ? 0 : 2);
  }

  /**
   * Convertit un montant depuis une devise vers EUR (prix catalogue).
   *
   * @param float $amount Montant dans la devise source
   * @param string $fromCurrency Devise source (CDF ou USD)
   * @return float Montant en EUR
   */
  public function convertToEur(float $amount, string $fromCurrency): float
  {
    $currency = strtoupper($fromCurrency);

    if ($currency === 'EUR') {
      return round($amount, 2);
    }

    $rate = $this->getRateFromEur($currency);

    if ($rate <= 0) {
      return round($amount, 2);
    }

    return round($amount / $rate, 2);
  }

  /**
   * Formate le total d'une commande dans sa devise d'origine.
   *
   * @param float|string $amount Montant commande
   * @param string|null $currency Devise commande
   * @return string Montant formaté
   */
  public function formatOrderAmount(float|string $amount, ?string $currency): string
  {
    return $this->format((float) $amount, $currency ?: $this->primaryCurrency());
  }

  /**
   * Devise principale configurée en admin.
   *
   * @return string Code devise (CDF ou USD)
   */
  public function primaryCurrency(): string
  {
    return $this->settings->primaryCurrency();
  }

  /**
   * Retourne le taux de conversion EUR → devise.
   *
   * @param string $currency Code devise cible
   * @return float Taux multiplicateur
   */
  public function getRateFromEur(string $currency): float
  {
    return match (strtoupper($currency)) {
      'CDF' => (float) $this->settings->get('rate_eur_cdf', 2850),
      'USD' => (float) $this->settings->get('rate_eur_usd', 1.08),
      default => 1.0,
    };
  }

  /**
   * Formate un montant avec symbole devise.
   *
   * @param float $amount Montant
   * @param string|null $currency Devise (défaut : sélection session)
   * @return string Chaîne affichable
   */
  public function format(float $amount, ?string $currency = null): string
  {
    $currency = $currency ?? $this->selectedCurrency();

    $formatted = match (strtoupper($currency)) {
      'USD' => number_format($amount, 2, '.', ' ') . ' $',
      default => number_format($amount, 0, ',', ' ') . ' FC',
    };

    return $formatted;
  }

  /**
   * Formate un prix catalogue EUR dans la devise active.
   *
   * @param float $amountEur Prix stocké en EUR
   * @param string|null $currency Devise cible
   * @return string Prix formaté
   */
  public function formatFromEur(float $amountEur, ?string $currency = null): string
  {
    $currency = $currency ?? $this->selectedCurrency();

    return $this->format($this->convertFromEur($amountEur, $currency), $currency);
  }
}
