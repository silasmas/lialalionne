<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Filament\Resources\Categories\Schemas\CategoryForm;
use App\Filament\Resources\Categories\Tables\CategoriesTable;
use App\Models\Category;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour la gestion des catégories produits.
 */
class CategoryResource extends Resource
{
  protected static ?string $model = Category::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

  protected static string | \UnitEnum | null $navigationGroup = 'Catalogue';

  protected static ?int $navigationSort = 1;

  protected static ?string $modelLabel = 'catégorie';

  protected static ?string $pluralModelLabel = 'catégories';

  protected static ?string $recordTitleAttribute = 'name';

  /**
   * Configure le formulaire de création/édition.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return CategoryForm::configure($schema);
  }

  /**
   * Configure la table de listing.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return CategoriesTable::configure($table);
  }

  /**
   * @return array<int, string>
   */
  public static function getRelations(): array
  {
    return [];
  }

  /**
   * @return array<string, \Filament\Resources\Pages\PageRegistration>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListCategories::route('/'),
      'create' => CreateCategory::route('/create'),
      'edit' => EditCategory::route('/{record}/edit'),
    ];
  }
}
