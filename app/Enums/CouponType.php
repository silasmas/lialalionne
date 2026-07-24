<?php

namespace App\Enums;

/**
 * Types de remise d'un code promotionnel.
 */
enum CouponType: string
{
  case Percent = 'percent';
  case Fixed = 'fixed';

  /**
   * Retourne le libellé affichable du type.
   *
   * @return string Libellé en français
   */
  public function label(): string
  {
    return match ($this) {
      self::Percent => 'Pourcentage',
      self::Fixed => 'Montant fixe',
    };
  }
}
