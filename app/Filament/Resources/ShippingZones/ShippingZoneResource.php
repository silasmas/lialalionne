<?php

namespace App\Filament\Resources\ShippingZones;

use App\Filament\Resources\ShippingZones\Pages\CreateShippingZone;
use App\Filament\Resources\ShippingZones\Pages\EditShippingZone;
use App\Filament\Resources\ShippingZones\Pages\ListShippingZones;
use App\Filament\Resources\ShippingZones\RelationManagers\RatesRelationManager;
use App\Filament\Resources\ShippingZones\Schemas\ShippingZoneForm;
use App\Filament\Resources\ShippingZones\Tables\ShippingZonesTable;
use App\Models\ShippingZone;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour les zones de livraison.
 */
class ShippingZoneResource extends Resource
{
  protected static ?string $model = ShippingZone::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;

  protected static string | \UnitEnum | null $navigationGroup = 'Paramètres';

  protected static ?int $navigationSort = 1;

  protected static ?string $modelLabel = 'zone de livraison';

  protected static ?string $pluralModelLabel = 'zones de livraison';

  protected static ?string $recordTitleAttribute = 'name';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return ShippingZoneForm::configure($schema);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return ShippingZonesTable::configure($table);
  }

  /**
   * @return array<int, class-string>
   */
  public static function getRelations(): array
  {
    return [
      RatesRelationManager::class,
    ];
  }

  /**
   * @return array<string, \Filament\Resources\Pages\PageRegistration>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListShippingZones::route('/'),
      'create' => CreateShippingZone::route('/create'),
      'edit' => EditShippingZone::route('/{record}/edit'),
    ];
  }
}
