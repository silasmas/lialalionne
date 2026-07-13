<?php

namespace App\Filament\Resources\Orders\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Affichage en lecture seule des lignes de commande.
 */
class ItemsRelationManager extends RelationManager
{
  protected static string $relationship = 'items';

  protected static ?string $title = 'Articles';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return $schema->components([]);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('product_name')
      ->columns([
        TextColumn::make('product_name')
          ->label('Produit'),
        TextColumn::make('variant_name')
          ->label('Variante')
          ->placeholder('—'),
        TextColumn::make('sku')
          ->label('SKU'),
        TextColumn::make('quantity')
          ->label('Qté')
          ->numeric(),
        TextColumn::make('unit_price')
          ->label('Prix unit.')
          ->money('EUR'),
        TextColumn::make('total_price')
          ->label('Total')
          ->money('EUR'),
      ])
      ->headerActions([])
      ->recordActions([])
      ->toolbarActions([]);
  }

  /**
   * @return bool
   */
  public function isReadOnly(): bool
  {
    return true;
  }
}
