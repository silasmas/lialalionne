<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Schéma du formulaire client admin (consultation).
 */
class UserForm
{
  /**
   * Configure les champs du formulaire client.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Informations client')
          ->schema([
            TextInput::make('name')
              ->label('Nom')
              ->disabled(),
            TextInput::make('email')
              ->label('Email')
              ->email()
              ->disabled(),
            TextInput::make('phone')
              ->label('Téléphone')
              ->tel()
              ->disabled(),
            TextInput::make('loyalty_points_balance')
              ->label('Solde points fidélité')
              ->numeric()
              ->minValue(0)
              ->helperText('Ajustable manuellement (geste commercial, correction).'),
          ])
          ->columns(2),
      ]);
  }
}
