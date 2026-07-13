<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\InstallationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Vérifie l'assistant d'installation HTTP.
 */
class InstallControllerTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Affiche la page d'installation.
   */
  public function testInstallPageLoadsWithoutLivewire(): void
  {
    User::query()->where('is_admin', true)->delete();

    $this->get('/install')
      ->assertOk()
      ->assertSee('Installation Lialalionne')
      ->assertSee('Exécuter les migrations')
      ->assertDontSee('wire:click');
  }

  /**
   * Exécute les migrations via POST classique.
   */
  public function testRunMigrationsViaPost(): void
  {
    $this->mock(\App\Services\SetupService::class, function ($mock): void {
      $mock->shouldReceive('runMigrations')->once()->andReturn([
        'success' => true,
        'message' => 'Migrations exécutées avec succès.',
      ]);
    });

    $this->post('/install/migrate')
      ->assertRedirect(route('install.setup'))
      ->assertSessionHas('install_flash_type', 'success');
  }

  /**
   * Crée un super administrateur via POST classique.
   */
  public function testCreateSuperAdminViaPost(): void
  {
    User::query()->where('is_admin', true)->delete();

    $this->post('/install/admin', [
      'adminName' => 'Test Admin',
      'adminEmail' => 'admin-install@test.local',
      'adminPassword' => 'password123',
      'adminPassword_confirmation' => 'password123',
    ])
      ->assertRedirect(route('install.setup'))
      ->assertSessionHas('install_flash_type', 'success');

    $this->assertTrue(
      User::query()->where('email', 'admin-install@test.local')->where('is_admin', true)->exists()
    );
  }
}
