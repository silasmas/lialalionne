<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderAddress;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Peuple les commandes, adresses et paiements de démonstration.
 */
class OrderSeeder extends Seeder
{
  /**
   * Crée des commandes avec différents statuts et historiques.
   *
   * @return void
   */
  public function run(): void
  {
    $customers = User::query()->where('is_admin', false)->get();
    $products = Product::query()->with('variants')->get();

    if ($customers->isEmpty() || $products->isEmpty()) {
      return;
    }

    $ordersData = [
      ['status' => OrderStatus::Delivered, 'method' => PaymentMethod::Stripe, 'payment' => PaymentStatus::Paid, 'days_ago' => 15, 'tracking' => 'FR1234567890'],
      ['status' => OrderStatus::Shipped, 'method' => PaymentMethod::Stripe, 'payment' => PaymentStatus::Paid, 'days_ago' => 3, 'tracking' => 'FR9876543210'],
      ['status' => OrderStatus::Processing, 'method' => PaymentMethod::Stripe, 'payment' => PaymentStatus::Paid, 'days_ago' => 1, 'tracking' => null],
      ['status' => OrderStatus::Paid, 'method' => PaymentMethod::Paypal, 'payment' => PaymentStatus::Paid, 'days_ago' => 0, 'tracking' => null],
      ['status' => OrderStatus::Pending, 'method' => PaymentMethod::Stripe, 'payment' => PaymentStatus::Pending, 'days_ago' => 0, 'tracking' => null],
      ['status' => OrderStatus::Cancelled, 'method' => PaymentMethod::Stripe, 'payment' => PaymentStatus::Failed, 'days_ago' => 7, 'tracking' => null],
    ];

    $addresses = [
      ['first_name' => 'Marie', 'last_name' => 'Dupont', 'city' => 'Paris', 'postal_code' => '75011', 'address_line_1' => '12 rue de la Roquette'],
      ['first_name' => 'Sophie', 'last_name' => 'Martin', 'city' => 'Lyon', 'postal_code' => '69002', 'address_line_1' => '8 place Bellecour'],
      ['first_name' => 'Aïcha', 'last_name' => 'Benali', 'city' => 'Marseille', 'postal_code' => '13001', 'address_line_1' => '45 la Canebière'],
      ['first_name' => 'Julie', 'last_name' => 'Leroy', 'city' => 'Bordeaux', 'postal_code' => '33000', 'address_line_1' => '3 cours de l\'Intendance'],
      ['first_name' => 'Camille', 'last_name' => 'Rousseau', 'city' => 'Nantes', 'postal_code' => '44000', 'address_line_1' => '17 quai de la Fosse'],
    ];

    foreach ($ordersData as $index => $config) {
      $customer = $customers[$index % $customers->count()];
      $address = $addresses[$index % count($addresses)];
      $orderProducts = $products->random(min(3, $products->count()));

      $subtotal = 0;
      $itemsPayload = [];

      foreach ($orderProducts as $product) {
        $variant = $product->variants->first();
        $quantity = random_int(1, 2);
        $unitPrice = (float) ($variant?->price ?? $product->price);
        $lineTotal = $unitPrice * $quantity;
        $subtotal += $lineTotal;

        $itemsPayload[] = [
          'product' => $product,
          'variant' => $variant,
          'quantity' => $quantity,
          'unit_price' => $unitPrice,
          'total_price' => $lineTotal,
        ];
      }

      $shippingAmount = $subtotal >= 60 ? 0 : 5.90;
      $total = $subtotal + $shippingAmount;
      $createdAt = now()->subDays($config['days_ago']);

      $order = Order::query()->create([
        'order_number' => 'LL-' . strtoupper(substr(md5((string) $index . $customer->id), 0, 8)),
        'user_id' => $customer->id,
        'status' => $config['status'],
        'payment_method' => $config['method'],
        'subtotal' => $subtotal,
        'shipping_amount' => $shippingAmount,
        'discount_amount' => 0,
        'tax_amount' => 0,
        'total' => $total,
        'currency' => 'EUR',
        'tracking_number' => $config['tracking'],
        'shipped_at' => in_array($config['status'], [OrderStatus::Shipped, OrderStatus::Delivered], true)
          ? $createdAt->copy()->addDay()
          : null,
        'delivered_at' => $config['status'] === OrderStatus::Delivered
          ? $createdAt->copy()->addDays(4)
          : null,
        'created_at' => $createdAt,
        'updated_at' => $createdAt,
      ]);

      foreach ($itemsPayload as $item) {
        OrderItem::query()->create([
          'order_id' => $order->id,
          'product_id' => $item['product']->id,
          'product_variant_id' => $item['variant']?->id,
          'product_name' => $item['product']->name,
          'variant_name' => $item['variant']?->name,
          'sku' => $item['variant']?->sku ?? $item['product']->sku,
          'quantity' => $item['quantity'],
          'unit_price' => $item['unit_price'],
          'total_price' => $item['total_price'],
        ]);
      }

      OrderAddress::query()->create([
        'order_id' => $order->id,
        'type' => 'shipping',
        'first_name' => $address['first_name'],
        'last_name' => $address['last_name'],
        'phone' => $customer->phone,
        'address_line_1' => $address['address_line_1'],
        'address_line_2' => null,
        'city' => $address['city'],
        'state' => null,
        'postal_code' => $address['postal_code'],
        'country' => 'FR',
      ]);

      Payment::query()->create([
        'order_id' => $order->id,
        'method' => $config['method'],
        'status' => $config['payment'],
        'amount' => $total,
        'currency' => 'EUR',
        'transaction_id' => $config['payment'] === PaymentStatus::Paid
          ? 'txn_' . strtoupper(substr(md5($order->order_number), 0, 12))
          : null,
        'paid_at' => $config['payment'] === PaymentStatus::Paid ? $createdAt : null,
      ]);
    }
  }
}
