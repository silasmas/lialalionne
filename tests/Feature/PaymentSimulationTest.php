<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du paiement simulé (sans passerelle configurée).
 */
class PaymentSimulationTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie qu'une commande simulée est confirmée via confirmCheckoutSession.
   *
   * @return void
   */
  public function testSimulatedCheckoutSessionConfirmsOrder(): void
  {
    config([
      'services.flexpay.token' => null,
      'services.flexpay.merchant' => null,
      'services.stripe.secret' => null,
    ]);

    $user = User::factory()->create(['is_admin' => false]);

    $order = Order::query()->create([
      'order_number' => 'LL-TEST-001',
      'user_id' => $user->id,
      'status' => OrderStatus::Pending,
      'payment_method' => PaymentMethod::Stripe,
      'subtotal' => 10000,
      'shipping_amount' => 0,
      'discount_amount' => 0,
      'tax_amount' => 0,
      'total' => 10000,
      'currency' => 'CDF',
      'fulfillment_type' => 'pickup',
    ]);

    Payment::query()->create([
      'order_id' => $order->id,
      'method' => PaymentMethod::Stripe,
      'status' => PaymentStatus::Pending,
      'amount' => 10000,
      'currency' => 'CDF',
      'transaction_id' => 'sim_LL-TEST-001',
      'metadata' => ['simulated' => true, 'gateway' => 'card'],
    ]);

    $result = app(PaymentService::class)->initiate($order->fresh(['payment']));

    $this->assertTrue($result['simulated']);
    $this->assertSame(OrderStatus::Paid, $order->fresh()->status);
  }
}
