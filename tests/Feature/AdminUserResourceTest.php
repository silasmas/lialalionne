<?php

namespace Tests\Feature;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Vérifie la ressource Utilisateurs admin et l'attribution des rôles Shield.
 */
class AdminUserResourceTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Prépare un super admin authentifié pour les tests Filament.
   *
   * @return User Administrateur avec rôle super_admin
   */
  private function actingAsSuperAdmin(): User
  {
    $guard = Utils::getFilamentAuthGuard();
    Role::findOrCreate(Utils::getSuperAdminName(), $guard);
    Role::findOrCreate(Utils::getPanelUserRoleName(), $guard);

    $admin = User::factory()->create([
      'is_admin' => true,
      'email' => 'shield-admin@test.local',
    ]);
    $admin->assignRole(Utils::getSuperAdminName());

    $this->actingAs($admin);

    return $admin;
  }

  /**
   * Un super admin peut lister les utilisateurs administrateurs.
   */
  public function testSuperAdminCanListAdminUsers(): void
  {
    $this->actingAsSuperAdmin();

    Livewire::test(ListAdminUsers::class)
      ->assertOk();
  }

  /**
   * Un super admin peut créer un utilisateur admin avec un rôle.
   */
  public function testSuperAdminCanCreateAdminUserWithRole(): void
  {
    $this->actingAsSuperAdmin();

    $panelUser = Role::findByName(Utils::getPanelUserRoleName(), Utils::getFilamentAuthGuard());

    Livewire::test(CreateAdminUser::class)
      ->fillForm([
        'name' => 'Éditeur Boutique',
        'email' => 'editeur@test.local',
        'phone' => '+243810000000',
        'password' => 'SecretAdmin1!',
        'password_confirmation' => 'SecretAdmin1!',
        'is_admin' => true,
        'roles' => [$panelUser->getKey()],
      ])
      ->call('create')
      ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'editeur@test.local')->first();

    $this->assertNotNull($created);
    $this->assertTrue($created->is_admin);
    $this->assertTrue($created->hasRole(Utils::getPanelUserRoleName()));
  }

  /**
   * Un client sans rôle admin n'accède pas au panel.
   */
  public function testCustomerCannotAccessAdminPanel(): void
  {
    $customer = User::factory()->create(['is_admin' => false]);

    $this->actingAs($customer)
      ->get('/admin')
      ->assertForbidden();
  }
}
