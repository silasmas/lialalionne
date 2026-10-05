<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour modérer les avis clients.
 */
class ReviewResource extends Resource
{
  protected static ?string $model = Review::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedStar;

  protected static string | \UnitEnum | null $navigationGroup = 'Marketing';

  protected static ?int $navigationSort = 2;

  protected static ?string $modelLabel = 'avis';

  protected static ?string $pluralModelLabel = 'avis clients';

  /**
   * Configure la table de listing.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return ReviewsTable::configure($table);
  }

  /**
   * Badge du menu de navigation : nombre d'avis en attente de modération.
   *
   * @return string|null Nombre affiché
   */
  public static function getNavigationBadge(): ?string
  {
    $count = static::getModel()::where('is_approved', false)->count();

    return $count > 0 ? (string) $count : null;
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
      'index' => ListReviews::route('/'),
    ];
  }
}
