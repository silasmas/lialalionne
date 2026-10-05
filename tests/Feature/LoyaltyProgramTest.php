<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\LoyaltyService;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du programme de fidélité (gain, utilisation, remboursement de points).
 */
class LoyaltyProgramTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Crée une commande de test en attente de paiement.
   *
   * @param User $user Client
   * @param float $subtotal Sous-total (= total ici, pas de livraison/remise)
   * @return Order Commande créée
   */
  private function makePendingOrder(User $user, float $subtotal): Order
  {
    $order = Order::query()->create([
      'order_number' => 'LL-LOY-' . uniqid(),
      'user_id' => $user->id,
      'status' => OrderStatus::Pending,
      'payment_method' => PaymentMethod::Stripe,
      'subtotal' => $subtotal,
      'shipping_amount' => 0,
      'discount_amount' => 0,
      'tax_amount' => 0,
      'total' => $subtotal,
      'currency' => 'EUR',
      'fulfillment_type' => 'pickup',
    ]);

    Payment::query()->create([
      'order_id' => $order->id,
      'method' => PaymentMethod::Stripe,
      'status' => PaymentStatus::Pending,
      'amount' => $subtotal,
      'currency' => 'EUR',
    ]);

    return $order;
  }

  /**
   * Vérifie qu'un paiement confirmé crédite 1 point par euro dépensé.
   *
   * @return void
   */
  public function testConfirmingPaymentEarnsPoints(): void
  {
    $user = User::factory()->create(['loyalty_points_balance' => 0]);
    $order = $this->makePendingOrder($user, 25.0);

    app(OrderService::class)->confirmPayment($order);

    $this->assertSame(25, $user->fresh()->loyalty_points_balance);
    $this->assertSame(25, $order->fresh()->loyalty_points_earned);
    $this->assertDatabaseHas('loyalty_point_transactions', [
      'user_id' => $user->id,
      'order_id' => $order->id,
      'points' => 25,
      'type' => 'earned',
    ]);
  }

  /**
   * Vérifie que la valeur des points est plafonnée par le sous-total et ne
   * peut jamais rendre la commande gratuite.
   *
   * @return void
   */
  public function testRedeemablePointsCappedBySubtotal(): void
  {
    $user = User::factory()->create(['loyalty_points_balance' => 1000]);

    $max = app(LoyaltyService::class)->maxRedeemablePoints($user, 5.0);

    // 5 € de sous-total, 10 points = 1 € → max 50 points malgré un solde de 1000.
    $this->assertSame(50, $max);
  }

  /**
   * Vérifie qu'utiliser des points débite immédiatement le solde du client.
   *
   * @return void
   */
  public function testRedeemingPointsDebitsBalance(): void
  {
    $user = User::factory()->create(['loyalty_points_balance' => 100]);
    $order = $this->makePendingOrder($user, 20.0);

    app(LoyaltyService::class)->redeemForOrder($user, $order, 30);

    $this->assertSame(70, $user->fresh()->loyalty_points_balance);
    $this->assertDatabaseHas('loyalty_point_transactions', [
      'user_id' => $user->id,
      'order_id' => $order->id,
      'points' => -30,
      'type' => 'redeemed',
    ]);
  }

  /**
   * Vérifie que l'annulation d'une commande rembourse les points utilisés.
   *
   * @return void
   */
  public function testCancellingOrderRefundsRedeemedPoints(): void
  {
    $user = User::factory()->create(['loyalty_points_balance' => 100]);
    $order = $this->makePendingOrder($user, 20.0);

    app(LoyaltyService::class)->redeemForOrder($user, $order, 30);
    $order->update(['loyalty_points_redeemed' => 30]);
    $this->assertSame(70, $user->fresh()->loyalty_points_balance);

    $order->update(['status' => OrderStatus::Cancelled]);

    $this->assertSame(100, $user->fresh()->loyalty_points_balance);
    $this->assertDatabaseHas('loyalty_point_transactions', [
      'user_id' => $user->id,
      'order_id' => $order->id,
      'points' => 30,
      'type' => 'refunded',
    ]);
  }

  /**
   * Vérifie qu'un client invité (sans compte) ne gagne jamais de points.
   *
   * @return void
   */
  public function testGuestOrderDoesNotEarnPoints(): void
  {
    $order = Order::query()->create([
      'order_number' => 'LL-LOY-GUEST',
      'user_id' => null,
      'status' => OrderStatus::Pending,
      'payment_method' => PaymentMethod::Stripe,
      'subtotal' => 15,
      'shipping_amount' => 0,
      'discount_amount' => 0,
      'tax_amount' => 0,
      'total' => 15,
      'currency' => 'EUR',
      'fulfillment_type' => 'pickup',
    ]);

    Payment::query()->create([
      'order_id' => $order->id,
      'method' => PaymentMethod::Stripe,
      'status' => PaymentStatus::Pending,
      'amount' => 15,
      'currency' => 'EUR',
    ]);

    app(OrderService::class)->confirmPayment($order);

    $this->assertSame(0, $order->fresh()->loyalty_points_earned);
    $this->assertDatabaseCount('loyalty_point_transactions', 0);
  }
}
