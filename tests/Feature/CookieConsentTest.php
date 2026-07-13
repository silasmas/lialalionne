<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du bandeau de consentement cookies.
 */
class CookieConsentTest extends TestCase
{
  use RefreshDatabase;
  /**
   * Vérifie que le bandeau cookies est présent sur la page d'accueil.
   *
   * @return void
   */
  public function testHomePageIncludesCookieConsentBanner(): void
  {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee('cookieConsentBanner', false);
    $response->assertSee('Consentement cookies', false);
    $response->assertSee('Gérer les cookies', false);
  }
}
