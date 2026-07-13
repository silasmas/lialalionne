<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Détecte l'état d'installation de l'application (BDD, migrations, admin).
 */
class InstallationService
{
  /**
   * @param EnvironmentFileService $environment Service .env
   */
  public function __construct(
    private readonly EnvironmentFileService $environment
  ) {
  }

  /**
   * Indique si l'application est prête pour la boutique et l'admin.
   *
   * @return bool True si installation complète
   */
  public function isInstalled(): bool
  {
    return $this->isCoreSetupComplete()
      && $this->environment->hasAppKey()
      && $this->environment->hasDatabaseConfig();
  }

  /**
   * Indique si la base est configurée (BDD, migrations, admin).
   *
   * @return bool True si l'essentiel est en place
   */
  public function isCoreSetupComplete(): bool
  {
    return $this->canConnectDatabase()
      && $this->isMigrationsTablePresent()
      && count($this->pendingMigrations()) === 0
      && $this->hasAdminUser();
  }

  /**
   * Indique si l'assistant d'installation doit s'afficher en priorité.
   *
   * @return bool True si installation incomplète
   */
  public function requiresInstallation(): bool
  {
    return !$this->isInstalled();
  }

  /**
   * Teste la connexion à la base de données.
   *
   * @return bool True si connexion OK
   */
  public function canConnectDatabase(): bool
  {
    if (!$this->environment->hasDatabaseConfig()) {
      return false;
    }

    try {
      DB::connection()->getPdo();

      return true;
    } catch (Throwable) {
      return false;
    }
  }

  /**
   * Indique si la table migrations existe.
   *
   * @return bool True si présente
   */
  public function isMigrationsTablePresent(): bool
  {
    if (!$this->canConnectDatabase()) {
      return false;
    }

    try {
      return Schema::hasTable('migrations');
    } catch (Throwable) {
      return false;
    }
  }

  /**
   * Retourne les migrations en attente.
   *
   * @return list<string> Noms de fichiers migration
   */
  public function pendingMigrations(): array
  {
    if (!$this->canConnectDatabase()) {
      return $this->migrationFiles();
    }

    try {
      Artisan::call('migrate:status', ['--no-ansi' => true]);
      $output = Artisan::output();
      $pending = [];

      foreach (explode(PHP_EOL, $output) as $line) {
        if (str_contains($line, 'Pending')) {
          if (preg_match('/\s(\S+\.php)\s+Pending/', $line, $matches) === 1) {
            $pending[] = $matches[1];
          }
        }
      }

      return $pending;
    } catch (Throwable) {
      return $this->migrationFiles();
    }
  }

  /**
   * Indique si au moins un administrateur existe.
   *
   * @return bool True si admin présent
   */
  public function hasAdminUser(): bool
  {
    if (!$this->canConnectDatabase() || !Schema::hasTable('users')) {
      return false;
    }

    try {
      return User::query()->where('is_admin', true)->exists();
    } catch (Throwable) {
      return false;
    }
  }

  /**
   * Indique si le lien symbolique storage est actif.
   *
   * @return bool True si public/storage existe
   */
  public function isStorageLinked(): bool
  {
    return is_link(public_path('storage')) || is_dir(public_path('storage'));
  }

  /**
   * Retourne un résumé d'état pour l'assistant d'installation.
   *
   * @return array<string, mixed> Statuts par étape
   */
  public function statusSummary(): array
  {
    return [
      'env_file' => $this->environment->exists(),
      'app_key' => $this->environment->hasAppKey(),
      'database_config' => $this->environment->hasDatabaseConfig(),
      'database_connection' => $this->canConnectDatabase(),
      'migrations_table' => $this->isMigrationsTablePresent(),
      'pending_migrations' => $this->pendingMigrations(),
      'storage_linked' => $this->isStorageLinked(),
      'admin_user' => $this->hasAdminUser(),
      'core_setup_complete' => $this->isCoreSetupComplete(),
      'installed' => $this->isInstalled(),
    ];
  }

  /**
   * @return list<string> Fichiers migration du projet
   */
  private function migrationFiles(): array
  {
    $files = glob(database_path('migrations/*.php')) ?: [];

    return array_map('basename', $files);
  }
}
