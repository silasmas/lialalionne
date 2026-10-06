<?php

namespace App\Filament\Resources\AdminUsers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des utilisateurs administrateurs.
 */
class AdminUsersTable
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
      ->defaultSort('name')
      ->columns([
        TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('email')
          ->label('Email')
          ->searchable()
          ->sortable(),
        TextColumn::make('phone')
          ->label('Téléphone')
          ->toggleable()
          ->searchable(),
        TextColumn::make('roles.name')
          ->label('Rôles')
          ->badge()
          ->separator(','),
        IconColumn::make('is_admin')
          ->label('Admin')
          ->boolean(),
        TextColumn::make('created_at')
          ->label('Créé le')
          ->dateTime('d/m/Y')
          ->sortable()
          ->toggleable(isToggledHiddenByDefault: true),
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
