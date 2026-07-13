<?php

namespace App\Enums;

/**
 * Méthodes de paiement acceptées sur la boutique.
 */
enum PaymentMethod: string
{
  case Stripe = 'stripe';
  case MobileMoney = 'mobile_money';
  case Paypal = 'paypal';
  case Cod = 'cod';

  /**
   * Retourne le libellé affichable de la méthode.
   *
   * @return string Libellé en français
   */
  public function label(): string
  {
    return match ($this) {
      self::Stripe => 'Carte bancaire',
      self::MobileMoney => 'Mobile Money',
      self::Paypal => 'PayPal',
      self::Cod => 'Paiement à la livraison',
    };
  }

  /**
   * Indique si la méthode passe par une passerelle en ligne.
   *
   * @return bool True si paiement en ligne
   */
  public function isOnline(): bool
  {
    return match ($this) {
      self::Stripe, self::MobileMoney, self::Paypal => true,
      self::Cod => false,
    };
  }
}
