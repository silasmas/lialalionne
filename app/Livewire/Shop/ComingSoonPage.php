<?php

namespace App\Livewire\Shop;

use App\Services\SiteSettingsService;
use Livewire\Component;

/**
 * Route legacy /coming-soon — redirige vers l'accueil.
 */
class ComingSoonPage extends Component
{
  /**
   * Redirige vers / où s'affiche le Coming Soon.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @return void
   */
  public function mount(SiteSettingsService $settings): void
  {
    if ($settings->isComingSoonEnabled()) {
      $this->redirect(route('home'), navigate: true);

      return;
    }

    $this->redirect(route('home'), navigate: true);
  }

  /**
   * @return \Illuminate\View\View Vue vide (redirection immédiate)
   */
  public function render()
  {
    return view('livewire.shop.coming-soon-page', [
      'title' => '',
      'message' => '',
      'launchAt' => null,
      'hasBypass' => false,
    ])->layout('layouts.minimal');
  }
}
