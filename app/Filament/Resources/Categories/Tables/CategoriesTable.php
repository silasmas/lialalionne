<?php

namespace App\Filament\Resources\Categories\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des catégories.
 */
class CategoriesTable
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
      ->defaultSort('sort_order')
      ->columns([
        TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('slug')
          ->label('Slug')
          ->searchable()
          ->toggleable(isToggledHiddenByDefault: true),
        ImageColumn::make('image')
          ->label('Image'),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
        TextColumn::make('products_count')
          ->label('Produits')
          ->counts('products')
          ->sortable(),
        TextColumn::make('sort_order')
          ->label('Ordre')
          ->numeric()
          ->sortable(),
      ])
      ->filters([
        TernaryFilter::make('is_active')
          ->label('Active'),
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
