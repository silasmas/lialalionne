<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Products\ProductResource;
use App\Models\Product;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Widget dashboard : produits avec stock faible.
 */
class LowStockProducts extends TableWidget
{
  protected static ?int $sort = 3;

  protected int|string|array $columnSpan = 'full';

  protected static ?string $heading = 'Produits — stock bas';

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->query(fn (): Builder => Product::query()
        ->where('track_stock', true)
        ->where('is_active', true)
        ->where('stock', '<=', 5)
        ->orderBy('stock'))
      ->columns([
        TextColumn::make('name')
          ->label('Produit'),
        TextColumn::make('sku')
          ->label('SKU'),
        TextColumn::make('stock')
          ->label('Stock')
          ->numeric()
          ->color('danger'),
        TextColumn::make('category.name')
          ->label('Catégorie'),
      ])
      ->recordActions([
        ViewAction::make()
          ->url(fn (Product $record): string => ProductResource::getUrl('edit', ['record' => $record])),
      ])
      ->paginated(false);
  }
}
