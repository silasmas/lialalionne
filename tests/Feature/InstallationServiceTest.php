<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie la détection d'installation.
 */
class InstallationServiceTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Considère l'application installée après migrations et admin.
   */
  public function testApplicationIsInstalledWithAdminAndMigrations(): void
  {
    $service = app(InstallationService::class);

    $this->assertTrue($service->canConnectDatabase());
    $this->assertTrue($service->hasAdminUser());
    $this->assertSame([], $service->pendingMigrations());
    $this->assertTrue($service->isInstalled());
  }

  /**
   * Redirige vers l'installateur si aucun admin n'existe.
   */
  public function testInstallRouteIsAvailableWhenNotInstalled(): void
  {
    User::query()->where('is_admin', true)->delete();

    $this->assertFalse(app(InstallationService::class)->hasAdminUser());

    $this->get('/')
      ->assertRedirect(route('install.setup'));
  }
}
