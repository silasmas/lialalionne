<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Peuple les utilisateurs admin et clients de démonstration.
 */
class UserSeeder extends Seeder
{
  /**
   * Crée le compte admin et les clients fictifs.
   *
   * @return void
   */
  public function run(): void
  {
    User::query()->create([
      'name' => 'Admin Lialalionne',
      'email' => 'admin@lialalionne.com',
      'phone' => '+33600000001',
      'password' => Hash::make('password'),
      'is_admin' => true,
      'email_verified_at' => now(),
    ]);

    $customers = [
      ['name' => 'Marie Dupont', 'email' => 'marie.dupont@email.com', 'phone' => '+33601020304'],
      ['name' => 'Sophie Martin', 'email' => 'sophie.martin@email.com', 'phone' => '+33605060708'],
      ['name' => 'Aïcha Benali', 'email' => 'aicha.benali@email.com', 'phone' => '+33609101112'],
      ['name' => 'Julie Leroy', 'email' => 'julie.leroy@email.com', 'phone' => '+33613141516'],
      ['name' => 'Camille Rousseau', 'email' => 'camille.rousseau@email.com', 'phone' => '+33617181920'],
    ];

    foreach ($customers as $customer) {
      User::query()->create([
        'name' => $customer['name'],
        'email' => $customer['email'],
        'phone' => $customer['phone'],
        'password' => Hash::make('password'),
        'is_admin' => false,
        'email_verified_at' => now(),
      ]);
    }
  }
}
