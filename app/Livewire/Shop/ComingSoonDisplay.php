<?php

namespace App\Livewire\Shop;

use App\Http\Middleware\RedirectIfComingSoon;
use App\Services\SiteSettingsService;
use Livewire\Component;

/**
 * Bloc Coming Soon réutilisable (accueil / route dédiée).
 */
class ComingSoonDisplay extends Component
{
  public string $bypassCode = '';

  public ?string $unlockMessage = null;

  /**
   * Tente d'activer l'accès manuel à la boutique via code secret.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @return void
   */
  public function unlock(SiteSettingsService $settings): void
  {
    $secret = $settings->comingSoonBypassSecret();

    if (!$secret) {
      $this->unlockMessage = 'Aucun code d\'accès n\'est configuré pour le moment.';

      return;
    }

    if (!hash_equals($secret, trim($this->bypassCode))) {
      $this->unlockMessage = 'Code d\'accès incorrect.';

      return;
    }

    session([RedirectIfComingSoon::BYPASS_SESSION_KEY => true]);
    $this->redirect(route('home'), navigate: true);
  }

  /**
   * Rendu du bloc Coming Soon.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(SiteSettingsService $settings)
  {
    return view('livewire.shop.coming-soon-page', [
      'title' => $settings->comingSoonTitle(),
      'message' => $settings->comingSoonMessage(),
      'launchAt' => $settings->comingSoonLaunchAt(),
      'hasBypass' => (bool) $settings->comingSoonBypassSecret(),
    ]);
  }
}
