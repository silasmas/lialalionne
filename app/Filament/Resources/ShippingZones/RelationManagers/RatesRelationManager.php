<?php

namespace App\Filament\Resources\ShippingZones\RelationManagers;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Gestion des tarifs de livraison par zone.
 */
class RatesRelationManager extends RelationManager
{
  protected static string $relationship = 'rates';

  protected static ?string $title = 'Tarifs';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->label('Nom')
          ->required()
          ->maxLength(255),
        TextInput::make('min_order_amount')
          ->label('Montant min.')
          ->numeric()
          ->prefix('€')
          ->default(0),
        TextInput::make('max_order_amount')
          ->label('Montant max.')
          ->numeric()
          ->prefix('€'),
        TextInput::make('price')
          ->label('Frais')
          ->required()
          ->numeric()
          ->prefix('€')
          ->minValue(0),
        TextInput::make('estimated_days_min')
          ->label('Délai min. (jours)')
          ->numeric()
          ->minValue(0),
        TextInput::make('estimated_days_max')
          ->label('Délai max. (jours)')
          ->numeric()
          ->minValue(0),
        Toggle::make('is_active')
          ->label('Actif')
          ->default(true),
      ]);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        TextColumn::make('name')
          ->label('Nom'),
        TextColumn::make('min_order_amount')
          ->label('Min.')
          ->money('EUR'),
        TextColumn::make('max_order_amount')
          ->label('Max.')
          ->money('EUR')
          ->placeholder('—'),
        TextColumn::make('price')
          ->label('Frais')
          ->money('EUR'),
        IconColumn::make('is_active')
          ->label('Actif')
          ->boolean(),
      ])
      ->headerActions([
        CreateAction::make(),
      ])
      ->recordActions([
        EditAction::make(),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
