<?php

namespace Tests\Unit;

use App\Models\User;
use Tests\TestCase;

class UserFilamentNameTest extends TestCase
{
  /**
   * Vérifie que Filament reçoit toujours une chaîne non vide.
   *
   * @return void
   */
  public function testFilamentNameFallsBackWhenNameIsNull(): void
  {
    $user = new User([
      'name' => null,
      'email' => 'admin@example.com',
      'phone' => null,
    ]);

    $this->assertSame('admin@example.com', $user->getFilamentName());
  }

  /**
   * Vérifie que le nom réel est prioritaire.
   *
   * @return void
   */
  public function testFilamentNameUsesNameWhenPresent(): void
  {
    $user = new User([
      'name' => 'Admin Lialalionne',
      'email' => 'admin@example.com',
    ]);

    $this->assertSame('Admin Lialalionne', $user->getFilamentName());
  }
}
