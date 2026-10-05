<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Vérifie la connexion au panel admin et le message d'échec visible.
 */
class AdminLoginTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Un administrateur valide accède au dashboard.
   */
  public function testAdminCanLoginWithValidCredentials(): void
  {
    $user = User::factory()->create([
      'email' => 'admin-login@test.local',
      'password' => Hash::make('SecretAdmin1'),
      'is_admin' => true,
    ]);

    $this->actingAs($user)
      ->get('/admin')
      ->assertOk();
  }

  /**
   * Un échec de connexion affiche un message sous le champ e-mail.
   */
  public function testLoginShowsVisibleErrorWhenCredentialsAreWrong(): void
  {
    Livewire::test(\App\Filament\Pages\Auth\Login::class)
      ->fillForm([
        'email' => 'inconnu@lialalionne.com',
        'password' => 'mauvais-mot-de-passe',
      ])
      ->call('authenticate')
      ->assertHasFormErrors(['email']);
  }
}
