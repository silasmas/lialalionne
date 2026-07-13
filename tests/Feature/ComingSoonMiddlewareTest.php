<?php

namespace Tests\Feature;

use App\Http\Middleware\RedirectIfComingSoon;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie le middleware Coming Soon.
 */
class ComingSoonMiddlewareTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Redirige la boutique vers Coming Soon quand le mode est actif.
   */
  public function testShopRoutesRedirectToComingSoonWhenEnabled(): void
  {
    app(SiteSettingsService::class)->setMany([
      'coming_soon_enabled' => true,
    ]);

    $this->get(route('home'))
      ->assertRedirect(route('coming-soon'));
  }

  /**
   * Autorise la boutique avec le code bypass en session.
   */
  public function testBypassSessionAllowsShopAccess(): void
  {
    app(SiteSettingsService::class)->setMany([
      'coming_soon_enabled' => true,
    ]);

    $this->withSession([RedirectIfComingSoon::BYPASS_SESSION_KEY => true])
      ->get(route('home'))
      ->assertOk();
  }

  /**
   * Affiche la page Coming Soon publique.
   */
  public function testComingSoonPageIsAccessibleWhenEnabled(): void
  {
    app(SiteSettingsService::class)->setMany([
      'coming_soon_enabled' => true,
      'coming_soon_title' => 'Ouverture imminente',
    ]);

    $this->get(route('coming-soon'))
      ->assertOk()
      ->assertSee('Ouverture imminente');
  }
}
