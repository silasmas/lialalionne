<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d'un code promo.
 */
class EditCoupon extends EditRecord
{
  protected static string $resource = CouponResource::class;

  /**
   * Actions d'en-tête de la fiche.
   *
   * @return array<int, DeleteAction>
   */
  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
    ];
  }
}
