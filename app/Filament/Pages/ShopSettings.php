<?php

namespace App\Filament\Pages;

use App\Enums\AuthMode;
use App\Services\SiteSettingsService;
use BackedEnum;
use Filament\Actions\Action;
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
 * Page admin de configuration boutique (auth client et paiements).
 */
class ShopSettings extends Page
{
  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

  protected static string | \UnitEnum | null $navigationGroup = 'Paramètres';

  protected static ?int $navigationSort = 0;

  protected static ?string $navigationLabel = 'Boutique';

  protected static ?string $title = 'Paramètres boutique';

  /**
   * @var array<string, mixed>|null
   */
  public ?array $data = [];

  /**
   * Charge les paramètres actuels dans le formulaire.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @return void
   */
  public function mount(SiteSettingsService $settings): void
  {
    $this->form->fill($settings->all());
  }

  /**
   * Schéma par défaut du formulaire de paramètres.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function defaultForm(Schema $schema): Schema
  {
    return $schema
      ->statePath('data');
  }

  /**
   * Champs de configuration boutique.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return $schema->components([
      Section::make('Authentification client')
        ->description('Mode de connexion et d\'inscription sur la boutique.')
        ->schema([
          Select::make('auth_mode')
            ->label('Mode d\'authentification')
            ->options(collect(AuthMode::cases())->mapWithKeys(
              fn (AuthMode $mode) => [$mode->value => $mode->label()]
            )->all())
            ->required()
            ->native(false),
        ]),
      Section::make('Paiements')
        ->description('FlexPay : Mobile Money + carte (initiate + callback webhook).')
        ->schema([
          Toggle::make('payment_card_enabled')
            ->label('Carte bancaire')
            ->default(true),
          Toggle::make('payment_mobile_money_enabled')
            ->label('Mobile Money')
            ->default(true),
        ]),
      Section::make('Devises')
        ->description('Prix catalogue en EUR, conversion CDF / USD au checkout.')
        ->schema([
          Select::make('currency_mode')
            ->label('Mode devises')
            ->options([
              'single' => 'Une seule monnaie',
              'dual' => 'Deux monnaies (client choisit)',
            ])
            ->required()
            ->native(false),
          Select::make('currency_primary')
            ->label('Devise principale')
            ->options(['CDF' => 'Franc congolais (CDF)', 'USD' => 'Dollar (USD)'])
            ->required(),
          Select::make('currency_secondary')
            ->label('Devise secondaire (mode dual)')
            ->options(['CDF' => 'Franc congolais (CDF)', 'USD' => 'Dollar (USD)'])
            ->required(),
          TextInput::make('rate_eur_cdf')
            ->label('Taux EUR → CDF')
            ->numeric()
            ->required(),
          TextInput::make('rate_eur_usd')
            ->label('Taux EUR → USD')
            ->numeric()
            ->required(),
        ])
        ->columns(2),
      Section::make('Retrait boutique')
        ->schema([
          Toggle::make('pickup_in_store_enabled')
            ->label('Proposer le retrait en boutique (sans frais de livraison)')
            ->default(true),
          TextInput::make('pickup_store_name')
            ->label('Nom du point de retrait')
            ->maxLength(255),
          Textarea::make('pickup_store_address')
            ->label('Adresse / horaires')
            ->rows(3),
        ]),
    ]);
  }

  /**
   * Persiste les paramètres et vide le cache.
   *
   * @param SiteSettingsService $settings Service paramètres
   * @return void
   */
  public function save(SiteSettingsService $settings): void
  {
    $data = $this->form->getState();
    $settings->setMany($data);

    Notification::make()
      ->success()
      ->title('Paramètres enregistrés')
      ->send();
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
        ->id('shop-settings-form')
        ->livewireSubmitHandler('save')
        ->footer([
          Actions::make([
            Action::make('save')
              ->label('Enregistrer')
              ->submit('save')
              ->keyBindings(['mod+s']),
          ]),
        ]),
    ]);
  }
}
