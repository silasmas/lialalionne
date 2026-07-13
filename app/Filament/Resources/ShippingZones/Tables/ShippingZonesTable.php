<?php

namespace App\Filament\Resources\ShippingZones\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des zones de livraison.
 */
class ShippingZonesTable
{
  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->columns([
        TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('rates_count')
          ->label('Tarifs')
          ->counts('rates'),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
      ])
      ->recordActions([
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
