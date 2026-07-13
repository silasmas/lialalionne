<?php

namespace App\Enums;

/**
 * Statuts possibles d'un paiement.
 */
enum PaymentStatus: string
{
  case Pending = 'pending';
  case Paid = 'paid';
  case Failed = 'failed';
  case Refunded = 'refunded';

  /**
   * Retourne le libellé affichable du statut.
   *
   * @return string Libellé en français
   */
  public function label(): string
  {
    return match ($this) {
      self::Pending => 'En attente',
      self::Paid => 'Payé',
      self::Failed => 'Échoué',
      self::Refunded => 'Remboursé',
    };
  }
}
