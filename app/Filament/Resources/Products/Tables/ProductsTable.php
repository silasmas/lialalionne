<?php

namespace App\Filament\Resources\Products\Tables;

use App\Services\CurrencyService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des produits.
 */
class ProductsTable
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
      ->columns([
        TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('category.name')
          ->label('Catégorie')
          ->searchable()
          ->sortable(),
        TextColumn::make('sku')
          ->label('SKU')
          ->searchable(),
        TextColumn::make('price')
          ->label('Prix boutique')
          ->formatStateUsing(fn ($state) => app(CurrencyService::class)->formatFromEur((float) $state))
          ->sortable(),
        TextColumn::make('stock')
          ->label('Stock')
          ->numeric()
          ->sortable()
          ->color(fn (int $state): string => $state <= 5 ? 'danger' : 'gray'),
        IconColumn::make('is_active')
          ->label('Actif')
          ->boolean(),
        IconColumn::make('is_featured')
          ->label('Vedette')
          ->boolean(),
      ])
      ->filters([
        SelectFilter::make('category_id')
          ->label('Catégorie')
          ->relationship('category', 'name'),
        TernaryFilter::make('is_active')
          ->label('Actif'),
        TernaryFilter::make('is_featured')
          ->label('Vedette'),
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
