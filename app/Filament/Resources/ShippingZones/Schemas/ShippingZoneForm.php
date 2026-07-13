<?php

namespace App\Filament\Resources\ShippingZones\Schemas;

use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Schéma du formulaire zone de livraison admin.
 */
class ShippingZoneForm
{
  /**
   * Configure les champs du formulaire zone de livraison.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Zone')
          ->schema([
            TextInput::make('name')
              ->label('Nom')
              ->required()
              ->maxLength(255),
            TagsInput::make('countries')
              ->label('Pays (codes ISO)')
              ->placeholder('FR, BE, CH...')
              ->helperText('Codes pays ISO à 2 lettres'),
            TagsInput::make('regions')
              ->label('Régions')
              ->placeholder('Île-de-France, ...'),
            Toggle::make('is_active')
              ->label('Active')
              ->default(true),
          ]),
      ]);
  }
}
