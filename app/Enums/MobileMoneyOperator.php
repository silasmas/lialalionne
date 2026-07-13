<?php

namespace App\Enums;

/**
 * Opérateurs Mobile Money disponibles en RD Congo.
 */
enum MobileMoneyOperator: string
{
  case Mpesa = 'mpesa';
  case Airtel = 'airtel';
  case Orange = 'orange';
  case AfriMoney = 'afrimoney';

  /**
   * Retourne le libellé affichable de l'opérateur.
   *
   * @return string Nom de l'opérateur
   */
  public function label(): string
  {
    return match ($this) {
      self::Mpesa => 'M-Pesa',
      self::Airtel => 'Airtel Money',
      self::Orange => 'Orange Money',
      self::AfriMoney => 'Afri Money',
    };
  }

  /**
   * Préfixes nationaux (sans indicatif 243) acceptés pour l'opérateur.
   *
   * @return list<string> Préfixes à 2 ou 3 chiffres
   */
  public function prefixes(): array
  {
    return match ($this) {
      self::Mpesa => ['081', '082', '083', '084'],
      self::Airtel => ['097', '098', '099'],
      self::Orange => ['085', '086', '087', '088', '089'],
      self::AfriMoney => ['090', '091'],
    };
  }

  /**
   * Préfixes nationaux sans le 0 initial (format après indicatif 243).
   *
   * @return list<string> Préfixes à 2 chiffres
   */
  public function nationalPrefixes(): array
  {
    return array_map(
      fn (string $prefix) => ltrim($prefix, '0'),
      $this->prefixes()
    );
  }

  /**
   * Texte d'aide listant les préfixes locaux et internationaux.
   *
   * @return string Indication pour l'utilisateur
   */
  public function prefixHint(): string
  {
    $local = implode(', ', $this->prefixes());
    $international = implode(', ', array_map(
      fn (string $prefix) => '243 ' . ltrim($prefix, '0'),
      $this->prefixes()
    ));

    return $international . ' ou ' . $local;
  }

  /**
   * Exemple de numéro pour le placeholder du champ.
   *
   * @return string Numéro exemple
   */
  public function placeholder(): string
  {
    return match ($this) {
      self::Mpesa => '243 81 234 5678',
      self::Airtel => '243 99 123 4567',
      self::Orange => '243 85 987 6543',
      self::AfriMoney => '243 90 555 1234',
    };
  }

  /**
   * Liste des opérateurs pour les formulaires.
   *
   * @return list<self> Cas enum
   */
  public static function all(): array
  {
    return self::cases();
  }
}
