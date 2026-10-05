<?php

namespace App\Services;

use App\Models\LoyaltyPointTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Programme de fidélité : points gagnés par achat, échangeables contre une
 * réduction sur une commande future.
 *
 * Barème : 1 point par 1 € dépensé (hors livraison) ; 10 points = 1 € de
 * réduction. Un client dépensant 20 € gagne 20 points, soit 2 € (10 %) de
 * réduction disponible pour un prochain achat.
 */
class LoyaltyService
{
  private const POINTS_PER_EUR_EARNED = 1;

  private const EUR_PER_POINT_REDEEMED = 0.10;

  /**
   * Calcule le nombre de points gagnés pour un montant dépensé.
   *
   * @param float $subtotalEur Sous-total hors livraison, en EUR
   * @return int Points gagnés
   */
  public function pointsForAmount(float $subtotalEur): int
  {
    return (int) floor(max(0, $subtotalEur) * self::POINTS_PER_EUR_EARNED);
  }

  /**
   * Valeur en EUR d'un nombre de points.
   *
   * @param int $points Nombre de points
   * @return float Valeur en EUR
   */
  public function eurValueOfPoints(int $points): float
  {
    return round(max(0, $points) * self::EUR_PER_POINT_REDEEMED, 2);
  }

  /**
   * Solde de points disponible pour un client.
   *
   * @param User|null $user Client (null = invité, toujours 0)
   * @return int Solde de points
   */
  public function balance(?User $user): int
  {
    return $user?->loyalty_points_balance ?? 0;
  }

  /**
   * Nombre maximal de points utilisables sur une commande (limité par le
   * solde du client et par le sous-total, pour ne jamais rendre la
   * commande gratuite via les points seuls).
   *
   * @param User|null $user Client
   * @param float $subtotalEur Sous-total de la commande en EUR
   * @return int Points maximum utilisables
   */
  public function maxRedeemablePoints(?User $user, float $subtotalEur): int
  {
    if (!$user || $subtotalEur <= 0) {
      return 0;
    }

    $maxByBalance = $user->loyalty_points_balance;
    $maxBySubtotal = (int) floor($subtotalEur / self::EUR_PER_POINT_REDEEMED);

    return max(0, min($maxByBalance, $maxBySubtotal));
  }

  /**
   * Crédite les points gagnés pour une commande acceptée (payée ou COD) et
   * journalise le mouvement.
   *
   * @param Order $order Commande source
   * @return void
   */
  public function earnForOrder(Order $order): void
  {
    if (!$order->user_id || $order->loyalty_points_earned > 0) {
      return;
    }

    $subtotalEur = app(CurrencyService::class)->convertToEur((float) $order->subtotal, $order->currency);
    $points = $this->pointsForAmount($subtotalEur);

    if ($points <= 0) {
      return;
    }

    DB::transaction(function () use ($order, $points) {
      $order->user()->increment('loyalty_points_balance', $points);
      $order->update(['loyalty_points_earned' => $points]);

      LoyaltyPointTransaction::query()->create([
        'user_id' => $order->user_id,
        'order_id' => $order->id,
        'points' => $points,
        'type' => 'earned',
        'description' => 'Commande ' . $order->order_number,
      ]);
    });
  }

  /**
   * Débite immédiatement les points utilisés à la création d'une commande.
   *
   * @param User $user Client
   * @param Order $order Commande créée
   * @param int $points Points à utiliser
   * @return void
   */
  public function redeemForOrder(User $user, Order $order, int $points): void
  {
    if ($points <= 0) {
      return;
    }

    DB::transaction(function () use ($user, $order, $points) {
      $user->decrement('loyalty_points_balance', $points);

      LoyaltyPointTransaction::query()->create([
        'user_id' => $user->id,
        'order_id' => $order->id,
        'points' => -$points,
        'type' => 'redeemed',
        'description' => 'Commande ' . $order->order_number,
      ]);
    });
  }

  /**
   * Rembourse les points utilisés sur une commande annulée.
   *
   * @param Order $order Commande annulée
   * @return void
   */
  public function refundRedeemedPoints(Order $order): void
  {
    if (!$order->user_id || $order->loyalty_points_redeemed <= 0) {
      return;
    }

    $alreadyRefunded = LoyaltyPointTransaction::query()
      ->where('order_id', $order->id)
      ->where('type', 'refunded')
      ->exists();

    if ($alreadyRefunded) {
      return;
    }

    DB::transaction(function () use ($order) {
      $order->user()->increment('loyalty_points_balance', $order->loyalty_points_redeemed);

      LoyaltyPointTransaction::query()->create([
        'user_id' => $order->user_id,
        'order_id' => $order->id,
        'points' => $order->loyalty_points_redeemed,
        'type' => 'refunded',
        'description' => 'Annulation commande ' . $order->order_number,
      ]);
    });
  }
}
