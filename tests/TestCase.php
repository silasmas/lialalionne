<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
  /**
   * @return void
   */
  protected function setUp(): void
  {
    parent::setUp();
    $this->ensureTestAdminExists();
  }

  /**
   * Crée un administrateur de test pour simuler une installation complète.
   *
   * @return void
   */
  protected function ensureTestAdminExists(): void
  {
    try {
      if (!Schema::hasTable('users')) {
        return;
      }

      if (User::query()->where('is_admin', true)->exists()) {
        return;
      }

      User::query()->create([
        'name' => 'Admin Test',
        'email' => 'admin@test.com',
        'password' => Hash::make('password'),
        'is_admin' => true,
        'email_verified_at' => now(),
      ]);
    } catch (\Throwable) {
      //
    }
  }

  /**
   * @return void
   */
  protected function afterRefreshingDatabase()
  {
    $this->ensureTestAdminExists();
  }
}
