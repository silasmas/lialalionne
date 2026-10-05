<?php

namespace App\Filament\Resources\NewsletterSubscribers;

use App\Filament\Resources\NewsletterSubscribers\Pages\ListNewsletterSubscribers;
use App\Filament\Resources\NewsletterSubscribers\Tables\NewsletterSubscribersTable;
use App\Models\NewsletterSubscriber;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour consulter et exporter les inscrits newsletter.
 */
class NewsletterSubscriberResource extends Resource
{
  protected static ?string $model = NewsletterSubscriber::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

  protected static string | \UnitEnum | null $navigationGroup = 'Marketing';

  protected static ?int $navigationSort = 1;

  protected static ?string $modelLabel = 'inscrit newsletter';

  protected static ?string $pluralModelLabel = 'newsletter';

  protected static ?string $recordTitleAttribute = 'email';

  /**
   * Configure la table de listing.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return NewsletterSubscribersTable::configure($table);
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
      'index' => ListNewsletterSubscribers::route('/'),
    ];
  }
}
