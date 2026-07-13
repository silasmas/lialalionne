<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Filament\Resources\Orders\RelationManagers\ItemsRelationManager;
use App\Filament\Resources\Orders\Schemas\OrderForm;
use App\Filament\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour la gestion des commandes.
 */
class OrderResource extends Resource
{
  protected static ?string $model = Order::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

  protected static string | \UnitEnum | null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 1;

  protected static ?string $modelLabel = 'commande';

  protected static ?string $pluralModelLabel = 'commandes';

  protected static ?string $recordTitleAttribute = 'order_number';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return OrderForm::configure($schema);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return OrdersTable::configure($table);
  }

  /**
   * @return array<int, class-string>
   */
  public static function getRelations(): array
  {
    return [
      ItemsRelationManager::class,
    ];
  }

  /**
   * @return array<string, \Filament\Resources\Pages\PageRegistration>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListOrders::route('/'),
      'edit' => EditOrder::route('/{record}/edit'),
    ];
  }

  /**
   * Désactive la création manuelle (commandes via la boutique).
   *
   * @return bool
   */
  public static function canCreate(): bool
  {
    return false;
  }
}
