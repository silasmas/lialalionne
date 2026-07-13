<?php

namespace App\Services;

use App\Models\ShippingRate;
use App\Models\ShippingZone;
use Illuminate\Support\Collection;

/**
 * Service de calcul des frais de livraison par zone et tarif.
 */
class ShippingService
{
  /**
   * Retourne les tarifs applicables pour un pays et un sous-total donnés.
   *
   * @param float $subtotal Montant du panier
   * @param string $countryCode Code pays ISO (ex. FR)
   * @return Collection<int, ShippingRate> Tarifs disponibles
   */
  public function getAvailableRates(float $subtotal, string $countryCode = 'FR'): Collection
  {
    $zones = ShippingZone::query()
      ->where('is_active', true)
      ->where(function ($query) use ($countryCode) {
        $query->whereJsonContains('countries', $countryCode)
          ->orWhereNull('countries');
      })
      ->pluck('id');

    return ShippingRate::query()
      ->whereIn('shipping_zone_id', $zones)
      ->where('is_active', true)
      ->orderBy('price')
      ->get()
      ->filter(fn (ShippingRate $rate) => $this->rateApplies($rate, $subtotal))
      ->values();
  }

  /**
   * Calcule le montant des frais de livraison.
   *
   * @param float $subtotal Montant du panier
   * @param string $countryCode Code pays ISO
   * @param int|null $rateId Identifiant du tarif choisi (optionnel)
   * @return float Montant des frais de livraison
   */
  public function calculate(float $subtotal, string $countryCode = 'FR', ?int $rateId = null): float
  {
    if ($rateId) {
      $rate = ShippingRate::query()->find($rateId);

      if ($rate && $this->rateApplies($rate, $subtotal)) {
        return (float) $rate->price;
      }
    }

    $rates = $this->getAvailableRates($subtotal, $countryCode);

    if ($rates->isEmpty()) {
      return 0.0;
    }

    return (float) $rates->first()->price;
  }

  /**
   * Vérifie si un tarif s'applique au montant de commande.
   *
   * @param ShippingRate $rate Tarif à vérifier
   * @param float $subtotal Sous-total du panier
   * @return bool True si le tarif est applicable
   */
  private function rateApplies(ShippingRate $rate, float $subtotal): bool
  {
    if ($subtotal < (float) $rate->min_order_amount) {
      return false;
    }

    if ($rate->max_order_amount !== null && $subtotal > (float) $rate->max_order_amount) {
      return false;
    }

    return true;
  }
}
