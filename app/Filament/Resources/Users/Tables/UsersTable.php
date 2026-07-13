<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des clients.
 */
class UsersTable
{
  /**
   * Configure les colonnes et actions de la table.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->defaultSort('created_at', 'desc')
      ->columns([
        TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('email')
          ->label('Email')
          ->searchable(),
        TextColumn::make('phone')
          ->label('Téléphone')
          ->searchable(),
        TextColumn::make('orders_count')
          ->label('Commandes')
          ->counts('orders')
          ->sortable(),
        TextColumn::make('created_at')
          ->label('Inscrit le')
          ->dateTime('d/m/Y')
          ->sortable(),
      ])
      ->recordActions([
        EditAction::make(),
      ]);
  }
}
