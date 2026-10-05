<?php

namespace App\Filament\Resources\HomeSlides;

use App\Filament\Resources\HomeSlides\Pages\CreateHomeSlide;
use App\Filament\Resources\HomeSlides\Pages\EditHomeSlide;
use App\Filament\Resources\HomeSlides\Pages\ListHomeSlides;
use App\Filament\Resources\HomeSlides\Schemas\HomeSlideForm;
use App\Filament\Resources\HomeSlides\Tables\HomeSlidesTable;
use App\Models\HomeSlide;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour les slides d'accueil (photo ou vidéo).
 */
class HomeSlideResource extends Resource
{
  protected static ?string $model = HomeSlide::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

  protected static string | \UnitEnum | null $navigationGroup = 'Boutique';

  protected static ?int $navigationSort = 1;

  protected static ?string $modelLabel = 'slide';

  protected static ?string $pluralModelLabel = 'slides accueil';

  protected static ?string $recordTitleAttribute = 'title';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return HomeSlideForm::configure($schema);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return HomeSlidesTable::configure($table);
  }

  /**
   * @return array<string, \Filament\Resources\Pages\PageRegistration>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListHomeSlides::route('/'),
      'create' => CreateHomeSlide::route('/create'),
      'edit' => EditHomeSlide::route('/{record}/edit'),
    ];
  }
}
