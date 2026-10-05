<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Détecte l'état d'installation de l'application (BDD, migrations, admin).
 */
class InstallationService
{
  /**
   * Cache du dernier test de connexion dans la requête courante.
   */
  private ?string $cachedConnectionError = null;

  private bool $connectionChecked = false;

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
    return $this->databaseConnectionError() === null;
  }

  /**
   * Retourne le message d'erreur connexion BDD ou null si OK.
   *
   * @param bool $forceIgnoreCache Force un nouveau test (après changement .env)
   * @return string|null Message d'erreur
   */
  public function databaseConnectionError(bool $forceIgnoreCache = false): ?string
  {
    if ($this->connectionChecked && !$forceIgnoreCache) {
      return $this->cachedConnectionError;
    }

    $this->connectionChecked = true;

    if (!$this->environment->hasDatabaseConfig()) {
      $this->cachedConnectionError = 'DB_DATABASE ou DB_CONNECTION manquant dans le .env.';

      return $this->cachedConnectionError;
    }

    try {
      DB::connection()->getPdo();
      DB::select('select 1');

      $this->cachedConnectionError = null;

      return null;
    } catch (Throwable $exception) {
      $this->cachedConnectionError = $this->humanizeConnectionError($exception->getMessage());

      return $this->cachedConnectionError;
    }
  }

  /**
   * Invalide le cache de test BDD (après écriture .env / reconnect).
   *
   * @return void
   */
  public function forgetConnectionCache(): void
  {
    $this->connectionChecked = false;
    $this->cachedConnectionError = null;
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
    if (!$this->canConnectDatabase() || !$this->isMigrationsTablePresent()) {
      return $this->migrationFiles();
    }

    try {
      /** @var Migrator $migrator */
      $migrator = app('migrator');
      $paths = [database_path('migrations')];
      $files = $migrator->getMigrationFiles($paths);
      $ran = $migrator->getRepository()->getRan();
      $pending = [];

      foreach (array_keys($files) as $migrationName) {
        if (!in_array($migrationName, $ran, true)) {
          $pending[] = str_ends_with($migrationName, '.php')
            ? $migrationName
            : $migrationName . '.php';
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
   * Indique si le stockage public est utilisable.
   *
   * Sur Windows, `storage:link` crée une junction : PHP `is_link()` / `is_dir()`
   * peuvent renvoyer false alors que le chemin existe. Les fichiers sont de
   * toute façon servis via /media (PublicMediaController).
   *
   * @return bool True si le disque public est prêt
   */
  public function isStorageLinked(): bool
  {
    $link = public_path('storage');
    $disk = storage_path('app/public');

    if (is_link($link) || is_dir($link) || (file_exists($link) && !is_file($link))) {
      return true;
    }

    return is_dir($disk) && is_writable($disk);
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
   * Transforme une erreur PDO technique en message actionnable.
   *
   * @param string $message Message brut
   * @return string Message clarifié
   */
  private function humanizeConnectionError(string $message): string
  {
    $host = (string) config('database.connections.' . config('database.default') . '.host', '');

    if (
      str_contains($message, '2002')
      || str_contains($message, 'Operation not permitted')
      || str_contains($message, 'No such file or directory')
    ) {
      $hint = 'Sur un hébergement mutualisé, mettez DB_HOST=127.0.0.1 (et non « localhost », qui utilise un socket souvent bloqué).';

      if (strtolower($host) === 'localhost') {
        return $message . ' — ' . $hint;
      }

      return $message . ' — Vérifiez DB_HOST / DB_PORT / accès MySQL. ' . $hint;
    }

    if (str_contains($message, '1045') || str_contains($message, 'Access denied')) {
      return $message . ' — Identifiants MySQL incorrects (DB_USERNAME / DB_PASSWORD).';
    }

    if (str_contains($message, '1049') || str_contains($message, 'Unknown database')) {
      return $message . ' — Le nom de base (DB_DATABASE) est incorrect ou la base n\'existe pas encore.';
    }

    return $message;
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
