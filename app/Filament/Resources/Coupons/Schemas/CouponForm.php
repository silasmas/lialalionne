<?php

namespace App\Filament\Resources\Coupons\Schemas;

use App\Enums\CouponType;
use App\Models\Coupon;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Schéma du formulaire code promo admin.
 */
class CouponForm
{
  /**
   * Configure les champs du formulaire code promo.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Code')
          ->schema([
            TextInput::make('code')
              ->label('Code à partager')
              ->required()
              ->maxLength(50)
              ->unique(ignoreRecord: true)
              ->dehydrateStateUsing(fn (?string $state): string => Coupon::normalizeCode((string) $state))
              ->helperText('Ex. : BIENVENUE10 — les clients le saisiront au checkout.'),
            TextInput::make('name')
              ->label('Nom interne')
              ->required()
              ->maxLength(255)
              ->helperText('Visible uniquement dans l\'admin.'),
            Select::make('type')
              ->label('Type de remise')
              ->options(collect(CouponType::cases())->mapWithKeys(
                fn (CouponType $type) => [$type->value => $type->label()]
              ))
              ->required()
              ->live(),
            TextInput::make('value')
              ->label('Valeur')
              ->numeric()
              ->required()
              ->minValue(0.01)
              ->helperText(fn (Get $get): string => $get('type') === CouponType::Percent->value
                ? 'Pourcentage de réduction (ex. 10 pour 10 %).'
                : 'Montant fixe en EUR (devise catalogue).'),
            Toggle::make('is_active')
              ->label('Actif')
              ->default(true),
          ])
          ->columns(2),
        Section::make('Conditions')
          ->schema([
            TextInput::make('min_order_amount')
              ->label('Montant minimum (EUR)')
              ->numeric()
              ->minValue(0)
              ->helperText('Sous-total panier minimum en EUR. Laisser vide = aucun minimum.'),
            TextInput::make('max_discount_amount')
              ->label('Remise maximale (EUR)')
              ->numeric()
              ->minValue(0)
              ->helperText('Plafond utile surtout pour les pourcentages. Laisser vide = pas de plafond.'),
            TextInput::make('max_uses')
              ->label('Utilisations max (global)')
              ->numeric()
              ->integer()
              ->minValue(1)
              ->helperText('Nombre total de commandes autorisées. Laisser vide = illimité.'),
            TextInput::make('max_uses_per_user')
              ->label('Utilisations max par client')
              ->numeric()
              ->integer()
              ->minValue(1)
              ->helperText('Si renseigné, le client doit être connecté.'),
            DateTimePicker::make('starts_at')
              ->label('Valide à partir de'),
            DateTimePicker::make('ends_at')
              ->label('Valide jusqu\'à'),
            Textarea::make('description')
              ->label('Description')
              ->rows(3)
              ->columnSpanFull(),
          ])
          ->columns(2),
        Section::make('Statistiques')
          ->schema([
            TextInput::make('times_used')
              ->label('Utilisations')
              ->numeric()
              ->disabled()
              ->dehydrated(false)
              ->default(0),
          ])
          ->visibleOn('edit')
          ->columns(1),
      ]);
  }
}
