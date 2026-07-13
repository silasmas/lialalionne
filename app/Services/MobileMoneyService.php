<?php

namespace App\Services;

use App\Enums\MobileMoneyOperator;
use Illuminate\Validation\ValidationException;

/**
 * Validation et normalisation des numéros Mobile Money RDC.
 */
class MobileMoneyService
{
  /**
   * Normalise un numéro congolais au format 243XXXXXXXXX.
   *
   * @param string $phone Numéro saisi
   * @return string Numéro normalisé
   */
  public function normalizePhone(string $phone): string
  {
    $digits = preg_replace('/\D+/', '', $phone) ?? '';

    if ($digits === '') {
      return '';
    }

    if (str_starts_with($digits, '0')) {
      $digits = '243' . substr($digits, 1);
    } elseif (!str_starts_with($digits, '243')) {
      $digits = '243' . $digits;
    }

    return $digits;
  }

  /**
   * Vérifie que le numéro correspond à l'opérateur choisi.
   *
   * @param string $phone Numéro saisi
   * @param MobileMoneyOperator $operator Opérateur sélectionné
   * @param string $errorField Clé d'erreur Livewire
   * @return string Numéro normalisé si valide
   */
  public function validatePhoneForOperator(
    string $phone,
    MobileMoneyOperator $operator,
    string $errorField = 'mobileMoneyPhone'
  ): string {
    $normalized = $this->normalizePhone($phone);

    if (strlen($normalized) !== 12) {
      throw ValidationException::withMessages([
        $errorField => 'Numéro Mobile Money invalide. Utilisez le format 243 XX XXX XXXX.',
      ]);
    }

    $national = substr($normalized, 3);

    if (!$this->matchesOperator($national, $operator)) {
      throw ValidationException::withMessages([
        $errorField => 'Ce numéro ne correspond pas à ' . $operator->label() . '. Préfixes acceptés : ' . $operator->prefixHint() . '.',
      ]);
    }

    return $normalized;
  }

  /**
   * Indique si la partie nationale correspond aux préfixes de l'opérateur.
   *
   * @param string $national Numéro sans indicatif 243 (9 chiffres)
   * @param MobileMoneyOperator $operator Opérateur sélectionné
   * @return bool True si le préfixe est valide
   */
  private function matchesOperator(string $national, MobileMoneyOperator $operator): bool
  {
    foreach ($operator->nationalPrefixes() as $prefix) {
      if (str_starts_with($national, $prefix)) {
        return true;
      }
    }

    return false;
  }
}
