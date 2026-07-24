<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Ressource Filament pour les codes promotionnels.
 */
class CouponResource extends Resource
{
  protected static ?string $model = Coupon::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTicket;

  protected static string | \UnitEnum | null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 2;

  protected static ?string $modelLabel = 'code promo';

  protected static ?string $pluralModelLabel = 'codes promo';

  protected static ?string $recordTitleAttribute = 'code';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return CouponForm::configure($schema);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return CouponsTable::configure($table);
  }

  /**
   * @return array<string, \Filament\Resources\Pages\PageRegistration>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListCoupons::route('/'),
      'create' => CreateCoupon::route('/create'),
      'edit' => EditCoupon::route('/{record}/edit'),
    ];
  }
}
