<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du paiement à la livraison (COD).
 */
class CashOnDeliveryTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie qu'une commande COD est acceptée sans appel passerelle et sans
   * être marquée payée (encaissement manuel à la livraison).
   *
   * @return void
   */
  public function testCashOnDeliveryOrderIsAcceptedWithoutBeingMarkedPaid(): void
  {
    app(SiteSettingsService::class)->setMany(['payment_cod_enabled' => true]);

    $user = User::factory()->create(['is_admin' => false]);

    $order = Order::query()->create([
      'order_number' => 'LL-COD-001',
      'user_id' => $user->id,
      'status' => OrderStatus::Pending,
      'payment_method' => PaymentMethod::Cod,
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
      'method' => PaymentMethod::Cod,
      'status' => PaymentStatus::Pending,
      'amount' => 10000,
      'currency' => 'CDF',
    ]);

    $result = app(PaymentService::class)->initiate($order->fresh(['payment']));

    $fresh = $order->fresh(['payment']);

    $this->assertFalse($result['simulated']);
    $this->assertSame(OrderStatus::Processing, $fresh->status);
    $this->assertSame(PaymentStatus::Pending, $fresh->payment->status, 'Le paiement COD doit rester en attente jusqu\'à la livraison.');
    $this->assertSame('cod_LL-COD-001', $fresh->payment->transaction_id);
  }

  /**
   * Vérifie que COD est refusé si l'admin ne l'a pas activé.
   *
   * @return void
   */
  public function testCashOnDeliveryRejectedWhenDisabled(): void
  {
    app(SiteSettingsService::class)->setMany(['payment_cod_enabled' => false]);

    $user = User::factory()->create(['is_admin' => false]);

    $order = Order::query()->create([
      'order_number' => 'LL-COD-002',
      'user_id' => $user->id,
      'status' => OrderStatus::Pending,
      'payment_method' => PaymentMethod::Cod,
      'subtotal' => 10000,
      'shipping_amount' => 0,
      'discount_amount' => 0,
      'tax_amount' => 0,
      'total' => 10000,
      'currency' => 'CDF',
      'fulfillment_type' => 'pickup',
    ]);

    $this->expectException(\Illuminate\Validation\ValidationException::class);

    app(PaymentService::class)->initiate($order);
  }
}
