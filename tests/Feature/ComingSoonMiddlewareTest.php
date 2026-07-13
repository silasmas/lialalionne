<?php

namespace Tests\Feature;

use App\Http\Middleware\RedirectIfComingSoon;
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
   * Redirige la boutique vers l'accueil quand le mode est actif.
   */
  public function testShopRoutesRedirectToHomeWhenComingSoonEnabled(): void
  {
    app(SiteSettingsService::class)->setMany([
      'coming_soon_enabled' => true,
    ]);

    $this->get(route('shop.catalog'))
      ->assertRedirect(route('home'));
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
      ->get(route('shop.catalog'))
      ->assertOk();
  }

  /**
   * Affiche Coming Soon sur la page d'accueil.
   */
  public function testHomePageShowsComingSoonWhenEnabled(): void
  {
    app(SiteSettingsService::class)->setMany([
      'coming_soon_enabled' => true,
      'coming_soon_title' => 'Ouverture imminente',
    ]);

    $this->get(route('home'))
      ->assertOk()
      ->assertSee('Ouverture imminente');
  }
}
