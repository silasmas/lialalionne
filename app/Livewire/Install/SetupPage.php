<?php

namespace App\Livewire\Install;

use App\Services\EnvironmentFileService;
use App\Services\InstallationService;
use App\Services\SetupService;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;

/**
 * Assistant d'installation affiché tant que l'application n'est pas prête.
 */
class SetupPage extends Component
{
  /** @var array<string, string|null> */
  public array $envValues = [];

  public string $adminName = 'Admin Lialalionne';

  public string $adminEmail = '';

  public string $adminPassword = '';

  public string $adminPasswordConfirmation = '';

  public string $selectedSeeder = 'Database\\Seeders\\DatabaseSeeder';

  public bool $comingSoonEnabled = false;

  public string $comingSoonTitle = 'Lialalionne arrive bientôt';

  public string $comingSoonMessage = 'Notre boutique en ligne ouvre très prochainement.';

  public ?string $comingSoonLaunchAt = null;

  public string $comingSoonBypassSecret = '';

  public ?string $flashMessage = null;

  public string $flashType = 'success';

  public bool $isActionRunning = false;

  /** @var array<string, mixed> */
  public array $status = [];

  /**
   * Initialise le formulaire d'installation.
   *
   * @param InstallationService $installation Service installation
   * @param EnvironmentFileService $environment Service .env
   * @param SiteSettingsService $settings Service paramètres
   * @return void
   */
  public function mount(
    InstallationService $installation,
    EnvironmentFileService $environment,
    SiteSettingsService $settings
  ): void {
    $this->envValues = $environment->readEditableValues();
    $this->loadComingSoonSettings($settings);
    $this->refreshStatus($installation);
  }

  /**
   * Charge les paramètres Coming Soon depuis la base si disponible.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @return void
   */
  private function loadComingSoonSettings(SiteSettingsService $settings): void
  {
    try {
      if (!Schema::hasTable('settings')) {
        return;
      }

      $this->comingSoonEnabled = $settings->isComingSoonEnabled();
      $this->comingSoonTitle = $settings->comingSoonTitle();
      $this->comingSoonMessage = $settings->comingSoonMessage();
      $this->comingSoonLaunchAt = $settings->comingSoonLaunchAt();
      $this->comingSoonBypassSecret = $settings->comingSoonBypassSecret() ?? '';
    } catch (\Throwable) {
      //
    }
  }

  /**
   * Rafraîchit le résumé d'état affiché.
   *
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function refreshStatus(InstallationService $installation): void
  {
    $this->status = $installation->statusSummary();
  }

  /**
   * Enregistre les variables .env.
   *
   * @param SetupService $setup Service setup
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function saveEnvironment(SetupService $setup, InstallationService $installation): void
  {
    $this->isActionRunning = true;

    try {
      $result = $setup->saveEnvironment($this->envValues);
      $this->notify($result['message'], $result['success'] ? 'success' : 'error');
      $this->refreshStatus($installation);
    } finally {
      $this->isActionRunning = false;
    }
  }

  /**
   * Génère APP_KEY.
   *
   * @param SetupService $setup Service setup
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function generateAppKey(SetupService $setup, InstallationService $installation): void
  {
    $this->isActionRunning = true;

    try {
      $result = $setup->generateAppKey();
      $this->notify($result['message'], $result['success'] ? 'success' : 'error');
      $this->refreshStatus($installation);
    } finally {
      $this->isActionRunning = false;
    }
  }

  /**
   * Lance les migrations.
   *
   * @param SetupService $setup Service setup
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function runMigrations(SetupService $setup, InstallationService $installation): void
  {
    $this->isActionRunning = true;

    try {
      $result = $setup->runMigrations();
      $this->notify($result['message'], $result['success'] ? 'success' : 'error');
      $this->refreshStatus($installation);
    } finally {
      $this->isActionRunning = false;
    }
  }

  /**
   * Lance les seeders.
   *
   * @param SetupService $setup Service setup
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function runSeeders(SetupService $setup, InstallationService $installation): void
  {
    $this->isActionRunning = true;

    try {
      $result = $setup->runSeeders($this->selectedSeeder);
      $this->notify($result['message'], $result['success'] ? 'success' : 'error');
      $this->refreshStatus($installation);
    } finally {
      $this->isActionRunning = false;
    }
  }

  /**
   * Crée le lien storage.
   *
   * @param SetupService $setup Service setup
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function linkStorage(SetupService $setup, InstallationService $installation): void
  {
    $this->isActionRunning = true;

    try {
      $result = $setup->linkStorage();
      $this->notify($result['message'], $result['success'] ? 'success' : 'error');
      $this->refreshStatus($installation);
    } finally {
      $this->isActionRunning = false;
    }
  }

  /**
   * Crée le super administrateur.
   *
   * @param SetupService $setup Service setup
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function createSuperAdmin(SetupService $setup, InstallationService $installation): void
  {
    $this->validate([
      'adminName' => ['required', 'string', 'max:255'],
      'adminEmail' => ['required', 'email', 'max:255'],
      'adminPassword' => ['required', 'string', 'min:8', 'confirmed'],
    ], [], [
      'adminName' => 'nom',
      'adminEmail' => 'e-mail',
      'adminPassword' => 'mot de passe',
    ]);

    $this->isActionRunning = true;

    try {
      $result = $setup->createSuperAdmin(
        $this->adminName,
        $this->adminEmail,
        $this->adminPassword
      );
      $this->notify($result['message'], $result['success'] ? 'success' : 'error');
    } catch (\Illuminate\Validation\ValidationException $exception) {
      foreach ($exception->errors() as $field => $messages) {
        foreach ($messages as $message) {
          $this->addError($field, $message);
        }
      }
    } finally {
      $this->isActionRunning = false;
      $this->refreshStatus($installation);
    }
  }

  /**
   * Enregistre les paramètres Coming Soon.
   *
   * @param SetupService $setup Service setup
   * @param InstallationService $installation Service installation
   * @return void
   */
  public function saveComingSoon(SetupService $setup, InstallationService $installation): void
  {
    $this->isActionRunning = true;

    try {
      $result = $setup->saveComingSoonSettings([
        'coming_soon_enabled' => $this->comingSoonEnabled,
        'coming_soon_title' => $this->comingSoonTitle,
        'coming_soon_message' => $this->comingSoonMessage,
        'coming_soon_launch_at' => $this->comingSoonLaunchAt,
        'coming_soon_bypass_secret' => $this->comingSoonBypassSecret ?: null,
      ]);

      $this->notify($result['message'], $result['success'] ? 'success' : 'error');
      $this->refreshStatus($installation);
    } finally {
      $this->isActionRunning = false;
    }
  }

  /**
   * Ouvre l'administration Filament dans un nouvel onglet.
   *
   * @return void
   */
  public function goToAdmin(): void
  {
    $this->redirect(route('filament.admin.auth.login'), navigate: false);
  }

  /**
   * Ouvre la boutique (Coming Soon sur / si activé).
   *
   * @return void
   */
  public function goToSite(): void
  {
    $this->redirect(route('home'), navigate: false);
  }

  /**
   * Rendu de l'assistant d'installation.
   *
   * @param SetupService $setup Service setup
   * @param EnvironmentFileService $environment Service .env
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(SetupService $setup, EnvironmentFileService $environment)
  {
    return view('livewire.install.setup-page', [
      'editableKeys' => $environment->editableKeys(),
      'seeders' => $setup->availableSeeders(),
    ])->layout('layouts.minimal', [
      'title' => 'Installation — Lialalionne',
    ]);
  }

  /**
   * Affiche un message flash court.
   *
   * @param string $message Texte
   * @param string $type success|error
   * @return void
   */
  private function notify(string $message, string $type = 'success'): void
  {
    $this->flashMessage = $message;
    $this->flashType = $type;
  }
}
