<?php

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'un code promo.
 */
class CreateCoupon extends CreateRecord
{
  protected static string $resource = CouponResource::class;
}
