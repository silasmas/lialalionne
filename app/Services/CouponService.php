<?php

namespace App\Services;

use App\Enums\CouponType;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Validation et calcul des remises liées aux codes promo.
 */
class CouponService
{
  public const SESSION_KEY = 'shop.applied_coupon_code';

  /**
   * Recherche un coupon par code (insensible à la casse).
   *
   * @param string $code Code saisi
   * @return Coupon|null Coupon trouvé ou null
   */
  public function findByCode(string $code): ?Coupon
  {
    $normalized = Coupon::normalizeCode($code);

    if ($normalized === '') {
      return null;
    }

    return Coupon::query()
      ->whereRaw('UPPER(code) = ?', [$normalized])
      ->first();
  }

  /**
   * Valide un code pour un sous-total panier (EUR) et un utilisateur optionnel.
   *
   * @param string $code Code promo
   * @param float $subtotalEur Sous-total panier en EUR
   * @param User|null $user Client connecté
   * @param string|null $email E-mail invité pour la limite par client
   * @return Coupon Coupon validé
   */
  public function validateForCheckout(string $code, float $subtotalEur, ?User $user = null, ?string $email = null): Coupon
  {
    $coupon = $this->findByCode($code);

    if (!$coupon) {
      throw ValidationException::withMessages([
        'couponCode' => 'Ce code promo est invalide.',
      ]);
    }

    if (!$coupon->isCurrentlyValid()) {
      throw ValidationException::withMessages([
        'couponCode' => 'Ce code promo n\'est plus valable.',
      ]);
    }

    if ($coupon->hasReachedGlobalLimit()) {
      throw ValidationException::withMessages([
        'couponCode' => 'Ce code promo a atteint sa limite d\'utilisation.',
      ]);
    }

    if (
      $coupon->min_order_amount !== null
      && $subtotalEur < (float) $coupon->min_order_amount
    ) {
      throw ValidationException::withMessages([
        'couponCode' => 'Le montant minimum pour ce code n\'est pas atteint.',
      ]);
    }

    if ($coupon->max_uses_per_user !== null) {
      $normalizedEmail = $this->normalizeEmail($email ?? $user?->email);

      if ($user || $normalizedEmail) {
        $userUses = $this->countUsesForCustomer($coupon, $user, $normalizedEmail);

        if ($userUses >= $coupon->max_uses_per_user) {
          throw ValidationException::withMessages([
            'couponCode' => 'Vous avez déjà utilisé ce code promo le nombre de fois autorisé.',
          ]);
        }
      }
    }

    $discount = $this->calculateDiscountEur($coupon, $subtotalEur);

    if ($discount <= 0) {
      throw ValidationException::withMessages([
        'couponCode' => 'Ce code promo ne peut pas être appliqué à ce panier.',
      ]);
    }

    return $coupon;
  }

  /**
   * Calcule la remise en EUR sur le sous-total (hors livraison).
   *
   * @param Coupon $coupon Code promo
   * @param float $subtotalEur Sous-total panier en EUR
   * @return float Remise en EUR (jamais supérieure au sous-total)
   */
  public function calculateDiscountEur(Coupon $coupon, float $subtotalEur): float
  {
    $subtotalEur = max(0, $subtotalEur);

    $discount = match ($coupon->type) {
      CouponType::Percent => $subtotalEur * ((float) $coupon->value / 100),
      CouponType::Fixed => (float) $coupon->value,
    };

    if ($coupon->max_discount_amount !== null) {
      $discount = min($discount, (float) $coupon->max_discount_amount);
    }

    return round(min($discount, $subtotalEur), 2);
  }

  /**
   * Incrémente le compteur d'utilisations (transaction + verrou).
   *
   * @param Coupon $coupon Code utilisé
   * @return void
   */
  public function recordUsage(Coupon $coupon): void
  {
    DB::transaction(function () use ($coupon): void {
      $locked = Coupon::query()->whereKey($coupon->id)->lockForUpdate()->first();

      if (!$locked) {
        return;
      }

      if ($locked->hasReachedGlobalLimit()) {
        throw ValidationException::withMessages([
          'couponCode' => 'Ce code promo a atteint sa limite d\'utilisation.',
        ]);
      }

      $locked->increment('times_used');
    });
  }

  /**
   * Mémorise le code promo appliqué pour le panier et le checkout.
   *
   * @param string|null $code Code normalisé ou null pour effacer
   * @return void
   */
  public function rememberAppliedCode(?string $code): void
  {
    $normalized = Coupon::normalizeCode((string) $code);

    if ($normalized === '') {
      session()->forget(self::SESSION_KEY);

      return;
    }

    session([self::SESSION_KEY => $normalized]);
  }

  /**
   * Retourne le code promo mémorisé en session.
   *
   * @return string|null Code ou null
   */
  public function rememberedCode(): ?string
  {
    $code = session(self::SESSION_KEY);

    return is_string($code) && $code !== '' ? $code : null;
  }

  /**
   * Oublie le code promo mémorisé.
   *
   * @return void
   */
  public function forgetRememberedCode(): void
  {
    session()->forget(self::SESSION_KEY);
  }

  /**
   * Compte les commandes déjà passées avec ce coupon pour un client.
   *
   * @param Coupon $coupon Code promo
   * @param User|null $user Client connecté
   * @param string|null $email E-mail invité normalisé
   * @return int Nombre d'utilisations
   */
  private function countUsesForCustomer(Coupon $coupon, ?User $user, ?string $email): int
  {
    if (!$user && !$email) {
      return 0;
    }

    return Order::query()
      ->where('coupon_id', $coupon->id)
      ->where(function ($query) use ($user, $email): void {
        if ($user) {
          $query->orWhere('user_id', $user->id);
        }

        if ($email) {
          $query->orWhereHas('payment', function ($payment) use ($email): void {
            $payment->where('metadata->customer_email', $email);
          });
        }
      })
      ->count();
  }

  /**
   * Normalise une adresse e-mail pour les comparaisons.
   *
   * @param string|null $email E-mail saisi
   * @return string|null E-mail en minuscules ou null
   */
  private function normalizeEmail(?string $email): ?string
  {
    $normalized = strtolower(trim((string) $email));

    return $normalized !== '' ? $normalized : null;
  }
}
