<?php

namespace Database\Seeders;

use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Crée les rôles Shield de base et attribue super_admin aux comptes admin.
 */
class ShieldSeeder extends Seeder
{
  /**
   * Assure les rôles core et l'attribution aux administrateurs existants.
   *
   * @return void
   */
  public function run(): void
  {
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    $guard = Utils::getFilamentAuthGuard();

    Role::findOrCreate(Utils::getSuperAdminName(), $guard);
    Role::findOrCreate(Utils::getPanelUserRoleName(), $guard);

    User::query()
      ->where('is_admin', true)
      ->each(function (User $user): void {
        $user->syncRoles([Utils::getSuperAdminName()]);
      });
  }
}
