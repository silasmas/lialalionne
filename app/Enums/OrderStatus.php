<?php

namespace App\Enums;

/**
 * Statuts possibles d'une commande e-commerce.
 */
enum OrderStatus: string
{
  case Pending = 'pending';
  case Paid = 'paid';
  case Processing = 'processing';
  case Shipped = 'shipped';
  case Delivered = 'delivered';
  case Cancelled = 'cancelled';

  /**
   * Retourne le libellé affichable du statut.
   *
   * @return string Libellé en français
   */
  public function label(): string
  {
    return match ($this) {
      self::Pending => 'En attente',
      self::Paid => 'Payée',
      self::Processing => 'En préparation',
      self::Shipped => 'Expédiée',
      self::Delivered => 'Livrée',
      self::Cancelled => 'Annulée',
    };
  }
}
