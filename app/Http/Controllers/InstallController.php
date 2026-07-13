<?php

namespace App\Http\Controllers;

use App\Services\EnvironmentFileService;
use App\Services\InstallationService;
use App\Services\SetupService;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Assistant d'installation web (formulaires HTTP classiques, sans Livewire).
 */
class InstallController extends Controller
{
  /**
   * Affiche la page d'installation avec l'état courant.
   *
   * @param InstallationService $installation Service installation
   * @param EnvironmentFileService $environment Service .env
   * @param SetupService $setup Service actions setup
   * @param SiteSettingsService $settings Service paramètres
   * @return View Vue installation
   */
  public function show(
    InstallationService $installation,
    EnvironmentFileService $environment,
    SetupService $setup,
    SiteSettingsService $settings
  ): View {
    return view('install.setup', $this->viewData($installation, $environment, $setup, $settings));
  }

  /**
   * Enregistre le fichier .env.
   *
   * @param Request $request Requête HTTP
   * @param SetupService $setup Service setup
   * @return RedirectResponse Redirection avec message
   */
  public function saveEnvironment(Request $request, SetupService $setup): RedirectResponse
  {
    $keys = array_keys(app(EnvironmentFileService::class)->editableKeys());
    $values = $request->only($keys);
    $result = $setup->saveEnvironment($values);

    return $this->backWithResult($result);
  }

  /**
   * Génère APP_KEY.
   *
   * @param SetupService $setup Service setup
   * @return RedirectResponse Redirection avec message
   */
  public function generateAppKey(SetupService $setup): RedirectResponse
  {
    return $this->backWithResult($setup->generateAppKey());
  }

  /**
   * Exécute les migrations.
   *
   * @param SetupService $setup Service setup
   * @return RedirectResponse Redirection avec message
   */
  public function runMigrations(SetupService $setup): RedirectResponse
  {
    return $this->backWithResult($setup->runMigrations());
  }

  /**
   * Crée le lien storage public.
   *
   * @param SetupService $setup Service setup
   * @return RedirectResponse Redirection avec message
   */
  public function linkStorage(SetupService $setup): RedirectResponse
  {
    return $this->backWithResult($setup->linkStorage());
  }

  /**
   * Exécute un seeder.
   *
   * @param Request $request Requête HTTP
   * @param SetupService $setup Service setup
   * @return RedirectResponse Redirection avec message
   */
  public function runSeeders(Request $request, SetupService $setup): RedirectResponse
  {
    $class = (string) $request->input('selectedSeeder', 'Database\\Seeders\\DatabaseSeeder');

    return $this->backWithResult($setup->runSeeders($class));
  }

  /**
   * Crée le super administrateur.
   *
   * @param Request $request Requête HTTP
   * @param SetupService $setup Service setup
   * @return RedirectResponse Redirection avec message
   */
  public function createSuperAdmin(Request $request, SetupService $setup): RedirectResponse
  {
    $validated = $request->validate([
      'adminName' => ['required', 'string', 'max:255'],
      'adminEmail' => ['required', 'email', 'max:255'],
      'adminPassword' => ['required', 'string', 'min:8', 'confirmed'],
    ], [], [
      'adminName' => 'nom',
      'adminEmail' => 'e-mail',
      'adminPassword' => 'mot de passe',
    ]);

    try {
      $result = $setup->createSuperAdmin(
        $validated['adminName'],
        $validated['adminEmail'],
        $validated['adminPassword']
      );

      return $this->backWithResult($result);
    } catch (\Illuminate\Validation\ValidationException $exception) {
      return redirect()
        ->route('install.setup')
        ->withErrors($exception->errors())
        ->withInput();
    }
  }

  /**
   * Enregistre les paramètres Coming Soon.
   *
   * @param Request $request Requête HTTP
   * @param SetupService $setup Service setup
   * @return RedirectResponse Redirection avec message
   */
  public function saveComingSoon(Request $request, SetupService $setup): RedirectResponse
  {
    return $this->backWithResult($setup->saveComingSoonSettings([
      'coming_soon_enabled' => $request->boolean('comingSoonEnabled'),
      'coming_soon_title' => (string) $request->input('comingSoonTitle', ''),
      'coming_soon_message' => (string) $request->input('comingSoonMessage', ''),
      'coming_soon_launch_at' => $request->input('comingSoonLaunchAt') ?: null,
      'coming_soon_bypass_secret' => $request->input('comingSoonBypassSecret') ?: null,
    ]));
  }

  /**
   * Prépare les données communes à la vue installation.
   *
   * @param InstallationService $installation Service installation
   * @param EnvironmentFileService $environment Service .env
   * @param SetupService $setup Service setup
   * @param SiteSettingsService $settings Service paramètres
   * @return array<string, mixed> Données vue
   */
  private function viewData(
    InstallationService $installation,
    EnvironmentFileService $environment,
    SetupService $setup,
    SiteSettingsService $settings
  ): array {
    $envValues = $environment->readEditableValues();

    foreach (array_keys($envValues) as $key) {
      $oldValue = old($key);

      if ($oldValue !== null) {
        $envValues[$key] = $oldValue;
      }
    }

    $comingSoonEnabled = false;
    $comingSoonTitle = 'Lialalionne arrive bientôt';
    $comingSoonMessage = 'Notre boutique en ligne ouvre très prochainement.';
    $comingSoonLaunchAt = null;
    $comingSoonBypassSecret = '';

    try {
      if (Schema::hasTable('settings')) {
        $comingSoonEnabled = $settings->isComingSoonEnabled();
        $comingSoonTitle = $settings->comingSoonTitle();
        $comingSoonMessage = $settings->comingSoonMessage();
        $comingSoonLaunchAt = $settings->comingSoonLaunchAt();
        $comingSoonBypassSecret = $settings->comingSoonBypassSecret() ?? '';
      }
    } catch (\Throwable) {
      //
    }

    if (old('comingSoonEnabled') !== null) {
      $comingSoonEnabled = (bool) old('comingSoonEnabled');
    }

    return [
      'status' => $installation->statusSummary(),
      'dbError' => $installation->databaseConnectionError(),
      'editableKeys' => $environment->editableKeys(),
      'envValues' => $envValues,
      'seeders' => $setup->availableSeeders(),
      'selectedSeeder' => old('selectedSeeder', 'Database\\Seeders\\DatabaseSeeder'),
      'comingSoonEnabled' => $comingSoonEnabled,
      'comingSoonTitle' => old('comingSoonTitle', $comingSoonTitle),
      'comingSoonMessage' => old('comingSoonMessage', $comingSoonMessage),
      'comingSoonLaunchAt' => old('comingSoonLaunchAt', $comingSoonLaunchAt),
      'comingSoonBypassSecret' => old('comingSoonBypassSecret', $comingSoonBypassSecret),
    ];
  }

  /**
   * Redirige vers /install avec message flash.
   *
   * @param array{success: bool, message: string} $result Résultat action
   * @return RedirectResponse Redirection
   */
  private function backWithResult(array $result): RedirectResponse
  {
    return redirect()
      ->route('install.setup')
      ->with('install_flash_message', $result['message'])
      ->with('install_flash_type', $result['success'] ? 'success' : 'error');
  }
}
