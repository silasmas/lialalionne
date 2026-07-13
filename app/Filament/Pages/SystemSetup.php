<?php

namespace App\Filament\Pages;

use App\Services\EnvironmentFileService;
use App\Services\InstallationService;
use App\Services\SetupService;
use App\Services\SiteSettingsService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * Page admin : installation, maintenance et Coming Soon.
 */
class SystemSetup extends Page
{
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWrenchScrewdriver;

  protected static string | \UnitEnum | null $navigationGroup = 'Paramètres';

  protected static ?int $navigationSort = -1;

  protected static ?string $navigationLabel = 'Installation';

  protected static ?string $title = 'Installation & système';

  protected static ?string $slug = 'system-setup';

  public function getSubheading(): ?string
  {
    return $this->systemStatusDescription();
  }

  /**
   * @var array<string, mixed>|null
   */
  public ?array $data = [];

  public string $selectedSeeder = 'Database\\Seeders\\DatabaseSeeder';

  /**
   * Charge les paramètres et l'état système.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @param EnvironmentFileService $environment Service .env
   * @return void
   */
  public function mount(SiteSettingsService $settings, EnvironmentFileService $environment): void
  {
    $envValues = $environment->readEditableValues();
    $comingSoon = [
      'coming_soon_enabled' => $settings->get('coming_soon_enabled', false),
      'coming_soon_title' => $settings->comingSoonTitle(),
      'coming_soon_message' => $settings->comingSoonMessage(),
      'coming_soon_launch_at' => $settings->comingSoonLaunchAt(),
      'coming_soon_bypass_secret' => $settings->comingSoonBypassSecret(),
    ];

    $this->form->fill(array_merge($envValues, $comingSoon));
  }

  /**
   * Schéma par défaut du formulaire.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function defaultForm(Schema $schema): Schema
  {
    return $schema->statePath('data');
  }

  /**
   * Champs de configuration système.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    $environment = app(EnvironmentFileService::class);

    $envFields = [];

    foreach ($environment->editableKeys() as $key => $meta) {
      $field = TextInput::make($key)
        ->label($meta['label'])
        ->placeholder($meta['placeholder'] ?? null);

      if (($meta['type'] ?? 'text') === 'password') {
        $field->password()->revealable();
      }

      $envFields[] = $field;
    }

    return $schema->components([
      Section::make('Fichier .env')
        ->description('Paramètres de base lus et écrits dans le fichier .env.')
        ->schema($envFields)
        ->columns(2),
      Section::make('Coming Soon')
        ->description('Affiche une page « bientôt disponible » et bloque la boutique publique.')
        ->schema([
          Toggle::make('coming_soon_enabled')
            ->label('Activer Coming Soon')
            ->live(),
          TextInput::make('coming_soon_title')
            ->label('Titre')
            ->maxLength(255),
          Textarea::make('coming_soon_message')
            ->label('Message')
            ->rows(3),
          DatePicker::make('coming_soon_launch_at')
            ->label('Date de sortie')
            ->native(false),
          TextInput::make('coming_soon_bypass_secret')
            ->label('Code accès manuel')
            ->helperText('Permet à l\'équipe d\'accéder à la boutique pendant Coming Soon.'),
        ])
        ->columns(2),
    ]);
  }

  /**
   * Actions d'en-tête (migrations, seeders, storage).
   *
   * @return array<int, Action>
   */
  protected function getHeaderActions(): array
  {
    return [
      Action::make('runMigrations')
        ->label('Migrations')
        ->icon(Heroicon::OutlinedArrowPath)
        ->action(function (SetupService $setup): void {
          $result = $setup->runMigrations();
          $this->notifyResult($result);
        }),
      Action::make('linkStorage')
        ->label('Storage link')
        ->icon(Heroicon::OutlinedLink)
        ->action(function (SetupService $setup): void {
          $result = $setup->linkStorage();
          $this->notifyResult($result);
        }),
      Action::make('createSuperAdmin')
        ->label('Super admin')
        ->icon(Heroicon::OutlinedUserPlus)
        ->form([
          TextInput::make('name')->label('Nom')->required(),
          TextInput::make('email')->label('E-mail')->email()->required(),
          TextInput::make('password')->label('Mot de passe')->password()->required()->minLength(8),
          TextInput::make('password_confirmation')->label('Confirmation')->password()->required()->same('password'),
        ])
        ->action(function (array $data, SetupService $setup): void {
          try {
            $result = $setup->createSuperAdmin($data['name'], $data['email'], $data['password']);
            $this->notifyResult($result);
          } catch (\Illuminate\Validation\ValidationException $exception) {
            Notification::make()
              ->danger()
              ->title(collect($exception->errors())->flatten()->first() ?? 'Erreur')
              ->send();
          }
        }),
      Action::make('runSeeders')
        ->label('Seeder')
        ->icon(Heroicon::OutlinedCircleStack)
        ->form([
          Select::make('seeder')
            ->label('Seeder')
            ->options(collect(app(SetupService::class)->availableSeeders())
              ->mapWithKeys(fn (string $class): array => [$class => class_basename(str_replace('\\', '/', $class))])
              ->all())
            ->default('Database\\Seeders\\DatabaseSeeder')
            ->required(),
        ])
        ->action(function (array $data, SetupService $setup): void {
          $result = $setup->runSeeders($data['seeder']);
          $this->notifyResult($result);
        }),
      Action::make('previewComingSoon')
        ->label('Voir Coming Soon')
        ->icon(Heroicon::OutlinedEye)
        ->url(fn (): string => route('coming-soon'))
        ->openUrlInNewTab(),
    ];
  }

  /**
   * Enregistre .env et Coming Soon.
   *
   * @param SetupService $setup Service setup
   * @param EnvironmentFileService $environment Service .env
   * @return void
   */
  public function save(SetupService $setup, EnvironmentFileService $environment): void
  {
    $data = $this->form->getState();
    $envKeys = array_keys($environment->editableKeys());
    $envPayload = [];

    foreach ($envKeys as $key) {
      if (array_key_exists($key, $data)) {
        $envPayload[$key] = $data[$key];
      }
    }

    $envResult = $setup->saveEnvironment($envPayload);
    $comingSoonResult = $setup->saveComingSoonSettings([
      'coming_soon_enabled' => $data['coming_soon_enabled'] ?? false,
      'coming_soon_title' => $data['coming_soon_title'] ?? '',
      'coming_soon_message' => $data['coming_soon_message'] ?? '',
      'coming_soon_launch_at' => $data['coming_soon_launch_at'] ?? null,
      'coming_soon_bypass_secret' => $data['coming_soon_bypass_secret'] ?? null,
    ]);

    if (!$envResult['success']) {
      Notification::make()->danger()->title($envResult['message'])->send();

      return;
    }

    if (!$comingSoonResult['success']) {
      Notification::make()->danger()->title($comingSoonResult['message'])->send();

      return;
    }

    Notification::make()->success()->title('Paramètres enregistrés')->send();
  }

  /**
   * Génère APP_KEY.
   *
   * @param SetupService $setup Service setup
   * @return void
   */
  public function generateAppKey(SetupService $setup): void
  {
    $this->notifyResult($setup->generateAppKey());
  }

  /**
   * Contenu principal de la page Filament.
   *
   * @param Schema $schema Schéma page
   * @return Schema Schéma configuré
   */
  public function content(Schema $schema): Schema
  {
    return $schema->components([
      Form::make([EmbeddedSchema::make('form')])
        ->id('system-setup-form')
        ->livewireSubmitHandler('save')
        ->footer([
          Actions::make([
            Action::make('generateAppKey')
              ->label('Générer APP_KEY')
              ->action('generateAppKey'),
            Action::make('save')
              ->label('Enregistrer')
              ->submit('save')
              ->keyBindings(['mod+s']),
          ]),
        ]),
    ]);
  }

  /**
   * Résumé textuel de l'état système.
   *
   * @return string Description
   */
  private function systemStatusDescription(): string
  {
    $status = app(InstallationService::class)->statusSummary();
    $pending = count($status['pending_migrations'] ?? []);

    return sprintf(
      'BDD : %s · Migrations en attente : %d · Storage : %s · Admin : %s · Installé : %s',
      ($status['database_connection'] ?? false) ? 'OK' : 'KO',
      $pending,
      ($status['storage_linked'] ?? false) ? 'OK' : 'KO',
      ($status['admin_user'] ?? false) ? 'OK' : 'KO',
      ($status['installed'] ?? false) ? 'oui' : 'non'
    );
  }

  /**
   * Affiche une notification Filament depuis un résultat d'action.
   *
   * @param array{success: bool, message: string} $result Résultat action
   * @return void
   */
  private function notifyResult(array $result): void
  {
    $notification = Notification::make()->title($result['message']);

    if ($result['success']) {
      $notification->success()->send();

      return;
    }

    $notification->danger()->send();
  }
}
