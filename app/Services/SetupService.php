<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

/**
 * Actions d'installation : migrations, seeders, storage, admin, coming soon.
 */
class SetupService
{
  /**
   * @param InstallationService $installation Service état installation
   * @param EnvironmentFileService $environment Service .env
   * @param SiteSettingsService $settings Service paramètres boutique
   */
  public function __construct(
    private readonly InstallationService $installation,
    private readonly EnvironmentFileService $environment,
    private readonly SiteSettingsService $settings
  ) {
  }

  /**
   * Enregistre les variables .env de base.
   *
   * @param array<string, string|null> $values Variables à persister
   * @return array{success: bool, message: string}
   */
  public function saveEnvironment(array $values): array
  {
    try {
      $this->environment->update($values);

      return [
        'success' => true,
        'message' => 'Fichier .env mis à jour.',
      ];
    } catch (Throwable $exception) {
      return [
        'success' => false,
        'message' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Génère APP_KEY si nécessaire.
   *
   * @return array{success: bool, message: string}
   */
  public function generateAppKey(): array
  {
    try {
      $key = $this->environment->ensureAppKey();

      return [
        'success' => true,
        'message' => 'Clé application : ' . substr($key, 0, 12) . '…',
      ];
    } catch (Throwable $exception) {
      return [
        'success' => false,
        'message' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Exécute les migrations en attente.
   *
   * @return array{success: bool, message: string, output?: string}
   */
  public function runMigrations(): array
  {
    if (!$this->installation->canConnectDatabase()) {
      return [
        'success' => false,
        'message' => 'Connexion base de données impossible. Vérifiez le .env.',
      ];
    }

    try {
      Artisan::call('migrate', ['--force' => true]);
      $output = trim(Artisan::output());

      return [
        'success' => true,
        'message' => 'Migrations exécutées avec succès.',
        'output' => $output,
      ];
    } catch (Throwable $exception) {
      return [
        'success' => false,
        'message' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Exécute les seeders applicatifs.
   *
   * @param string|null $class Classe seeder optionnelle
   * @return array{success: bool, message: string, output?: string}
   */
  public function runSeeders(?string $class = null): array
  {
    if (!$this->installation->canConnectDatabase()) {
      return [
        'success' => false,
        'message' => 'Connexion base de données impossible.',
      ];
    }

    try {
      $parameters = ['--force' => true];

      if ($class) {
        $parameters['--class'] = $class;
      }

      Artisan::call('db:seed', $parameters);
      $output = trim(Artisan::output());

      return [
        'success' => true,
        'message' => $class ? "Seeder {$class} exécuté." : 'Seeders exécutés.',
        'output' => $output,
      ];
    } catch (Throwable $exception) {
      return [
        'success' => false,
        'message' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Crée le lien symbolique storage public.
   *
   * @return array{success: bool, message: string}
   */
  public function linkStorage(): array
  {
    if ($this->installation->isStorageLinked()) {
      return [
        'success' => true,
        'message' => 'Le lien storage est déjà actif.',
      ];
    }

    try {
      Artisan::call('storage:link');

      return [
        'success' => true,
        'message' => 'Lien storage créé.',
      ];
    } catch (Throwable $exception) {
      return [
        'success' => false,
        'message' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Crée le compte super administrateur.
   *
   * @param string $name Nom complet
   * @param string $email E-mail
   * @param string $password Mot de passe
   * @return array{success: bool, message: string}
   */
  public function createSuperAdmin(string $name, string $email, string $password): array
  {
    if (!$this->installation->canConnectDatabase()) {
      return [
        'success' => false,
        'message' => 'Base de données indisponible.',
      ];
    }

    if (User::query()->where('email', $email)->exists()) {
      $user = User::query()->where('email', $email)->first();

      if ($user && !$user->is_admin) {
        $user->update([
          'is_admin' => true,
          'password' => Hash::make($password),
          'name' => $name,
        ]);

        return [
          'success' => true,
          'message' => 'Compte existant promu administrateur.',
        ];
      }

      throw ValidationException::withMessages([
        'email' => 'Un compte avec cet e-mail existe déjà.',
      ]);
    }

    User::query()->create([
      'name' => $name,
      'email' => $email,
      'password' => Hash::make($password),
      'is_admin' => true,
      'email_verified_at' => now(),
    ]);

    return [
      'success' => true,
      'message' => 'Super administrateur créé.',
    ];
  }

  /**
   * Met à jour les paramètres Coming Soon.
   *
   * @param array<string, mixed> $values Paramètres coming soon
   * @return array{success: bool, message: string}
   */
  public function saveComingSoonSettings(array $values): array
  {
    try {
      if (!$this->installation->canConnectDatabase()) {
        throw new RuntimeException('La base de données n\'est pas prête.');
      }

      $payload = [
        'coming_soon_enabled' => (bool) ($values['coming_soon_enabled'] ?? false),
        'coming_soon_title' => (string) ($values['coming_soon_title'] ?? 'Bientôt disponible'),
        'coming_soon_message' => (string) ($values['coming_soon_message'] ?? ''),
        'coming_soon_launch_at' => $values['coming_soon_launch_at'] ?: null,
        'coming_soon_bypass_secret' => $values['coming_soon_bypass_secret'] ?: null,
      ];

      $this->settings->setMany($payload);

      return [
        'success' => true,
        'message' => 'Paramètres Coming Soon enregistrés.',
      ];
    } catch (Throwable $exception) {
      return [
        'success' => false,
        'message' => $exception->getMessage(),
      ];
    }
  }

  /**
   * Liste les seeders disponibles dans database/seeders.
   *
   * @return list<string> Classes seeder
   */
  public function availableSeeders(): array
  {
    $files = glob(database_path('seeders/*Seeder.php')) ?: [];
    $seeders = ['Database\\Seeders\\DatabaseSeeder'];

    foreach ($files as $file) {
      $base = basename($file, '.php');

      if ($base !== 'DatabaseSeeder') {
        $seeders[] = 'Database\\Seeders\\' . $base;
      }
    }

    return array_values(array_unique($seeders));
  }

  /**
   * Vérifie si le disque storage est accessible en écriture.
   *
   * @return bool True si accessible
   */
  public function canWriteStorage(): bool
  {
    try {
      Storage::disk('local')->put('install-check.txt', 'ok');
      Storage::disk('local')->delete('install-check.txt');

      return true;
    } catch (Throwable) {
      return false;
    }
  }
}
