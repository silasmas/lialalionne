<?php

namespace App\Livewire\Shop;

use App\Services\CurrencyService;
use App\Services\SiteSettingsService;
use Livewire\Component;

/**
 * Sélecteur de devise boutique (CDF / USD) si mode dual activé.
 */
class CurrencySelector extends Component
{
  public string $currency = 'CDF';

  public string $theme = 'default';

  /**
   * Initialise la devise depuis la session.
   *
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function mount(CurrencyService $currencyService): void
  {
    $this->currency = $currencyService->selectedCurrency();
  }

  /**
   * Change la devise affichée et en session.
   *
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function updatedCurrency(CurrencyService $currencyService): void
  {
    $currencyService->setSelectedCurrency($this->currency);
    $this->dispatch('currency-changed');
  }

  /**
   * Rendu du sélecteur (masqué si une seule devise).
   *
   * @param SiteSettingsService $settings Paramètres boutique
   * @param CurrencyService $currencyService Service devises
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(SiteSettingsService $settings, CurrencyService $currencyService)
  {
    if ($settings->currencyMode() !== 'dual') {
      return view('livewire.shop.currency-selector-empty');
    }

    $view = $this->theme === 'shopwise'
      ? 'livewire.shop.currency-selector-shopwise'
      : 'livewire.shop.currency-selector';

    return view($view, [
      'currencies' => $currencyService->availableCurrencies(),
    ]);
  }
}
