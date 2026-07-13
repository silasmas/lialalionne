<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\RelationManagers\ImagesRelationManager;
use App\Filament\Resources\Products\RelationManagers\VariantsRelationManager;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Product;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour la gestion des produits.
 */
class ProductResource extends Resource
{
  protected static ?string $model = Product::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShoppingBag;

  protected static string | \UnitEnum | null $navigationGroup = 'Catalogue';

  protected static ?int $navigationSort = 2;

  protected static ?string $modelLabel = 'produit';

  protected static ?string $pluralModelLabel = 'produits';

  protected static ?string $recordTitleAttribute = 'name';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return ProductForm::configure($schema);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return ProductsTable::configure($table);
  }

  /**
   * @return array<int, class-string>
   */
  public static function getRelations(): array
  {
    return [
      VariantsRelationManager::class,
      ImagesRelationManager::class,
    ];
  }

  /**
   * @return array<string, \Filament\Resources\Pages\PageRegistration>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListProducts::route('/'),
      'create' => CreateProduct::route('/create'),
      'edit' => EditProduct::route('/{record}/edit'),
    ];
  }
}
