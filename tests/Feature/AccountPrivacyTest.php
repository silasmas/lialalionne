<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;
use App\Livewire\Account\DashboardPage;

/**
 * Tests des fonctionnalités RGPD du compte client (export, suppression).
 */
class AccountPrivacyTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie que l'export de données déclenche bien un téléchargement.
   *
   * @return void
   */
  public function testExportDataDownloadsJson(): void
  {
    $user = User::factory()->create();

    Livewire::actingAs($user)
      ->test(DashboardPage::class)
      ->call('exportData')
      ->assertFileDownloaded();
  }

  /**
   * Vérifie qu'une confirmation incorrecte bloque la suppression.
   *
   * @return void
   */
  public function testDeleteAccountRequiresExactConfirmation(): void
  {
    $user = User::factory()->create();

    Livewire::actingAs($user)
      ->test(DashboardPage::class)
      ->set('deleteConfirmationText', 'oui supprimer')
      ->call('deleteAccount')
      ->assertHasErrors('deleteConfirmationText');

    $this->assertNotNull(User::find($user->id));
  }

  /**
   * Vérifie que la confirmation exacte supprime définitivement le compte
   * et déconnecte le client.
   *
   * @return void
   */
  public function testDeleteAccountRemovesUserAndLogsOut(): void
  {
    $user = User::factory()->create();

    Livewire::actingAs($user)
      ->test(DashboardPage::class)
      ->set('deleteConfirmationText', 'SUPPRIMER')
      ->call('deleteAccount')
      ->assertRedirect(route('home'));

    $this->assertNull(User::find($user->id));
    $this->assertGuest();
  }
}
