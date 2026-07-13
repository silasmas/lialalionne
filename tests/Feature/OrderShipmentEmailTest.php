<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Mail\OrderShipmentMail;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Tests de l'email automatique d'expédition.
 */
class OrderShipmentEmailTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie qu'un email est envoyé quand le numéro de suivi est renseigné.
   *
   * @return void
   */
  public function testShipmentEmailSentWhenTrackingNumberIsAdded(): void
  {
    Mail::fake();

    $user = User::factory()->create(['email' => 'client@example.com']);

    $order = Order::query()->create([
      'order_number' => 'LL-TEST-SHIP',
      'user_id' => $user->id,
      'status' => OrderStatus::Processing,
      'payment_method' => PaymentMethod::MobileMoney,
      'subtotal' => 10000,
      'shipping_amount' => 0,
      'discount_amount' => 0,
      'tax_amount' => 0,
      'total' => 10000,
      'currency' => 'CDF',
      'fulfillment_type' => 'delivery',
    ]);

    Payment::query()->create([
      'order_id' => $order->id,
      'method' => PaymentMethod::MobileMoney,
      'status' => PaymentStatus::Paid,
      'amount' => 10000,
      'currency' => 'CDF',
      'transaction_id' => 'mm_LL-TEST-SHIP',
      'metadata' => ['customer_email' => 'client@example.com'],
    ]);

    $order->update(['tracking_number' => 'CD123456789']);

    Mail::assertSent(OrderShipmentMail::class, function (OrderShipmentMail $mail) use ($order) {
      return $mail->order->is($order->fresh())
        && $mail->hasTo('client@example.com');
    });

    $this->assertSame('CD123456789', $order->fresh()->shipment_notified_tracking);
  }

  /**
   * Vérifie qu'aucun second email n'est envoyé si le numéro de suivi est inchangé.
   *
   * @return void
   */
  public function testShipmentEmailNotSentTwiceForSameTrackingNumber(): void
  {
    Mail::fake();

    $order = Order::query()->create([
      'order_number' => 'LL-TEST-SHIP2',
      'user_id' => User::factory()->create()->id,
      'status' => OrderStatus::Shipped,
      'payment_method' => PaymentMethod::Stripe,
      'subtotal' => 5000,
      'shipping_amount' => 0,
      'discount_amount' => 0,
      'tax_amount' => 0,
      'total' => 5000,
      'currency' => 'USD',
      'fulfillment_type' => 'pickup',
      'tracking_number' => 'TRACK-001',
      'shipment_notified_tracking' => 'TRACK-001',
    ]);

    $order->update(['status' => OrderStatus::Delivered]);

    Mail::assertNothingSent();
  }
}
