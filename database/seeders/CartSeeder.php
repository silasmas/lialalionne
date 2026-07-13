<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Peuple les paniers actifs de démonstration pour les clients.
 */
class CartSeeder extends Seeder
{
  /**
   * Crée des paniers avec articles pour 2 clients.
   *
   * @return void
   */
  public function run(): void
  {
    $customers = User::query()->where('is_admin', false)->limit(2)->get();
    $products = Product::query()->with('variants')->get();

    if ($customers->isEmpty() || $products->count() < 2) {
      return;
    }

    $cartItems = [
      [$products[0], null, 1],
      [$products[2], $products[2]->variants->first(), 2],
    ];

    $cart = Cart::query()->create([
      'user_id' => $customers[0]->id,
      'session_id' => null,
    ]);

    foreach ($cartItems as [$product, $variant, $quantity]) {
      CartItem::query()->create([
        'cart_id' => $cart->id,
        'product_id' => $product->id,
        'product_variant_id' => $variant?->id,
        'quantity' => $quantity,
        'unit_price' => $variant?->price ?? $product->price,
      ]);
    }

    $sessionCart = Cart::query()->create([
      'user_id' => null,
      'session_id' => 'demo-session-' . uniqid(),
    ]);

    $guestProduct = $products[4];
    CartItem::query()->create([
      'cart_id' => $sessionCart->id,
      'product_id' => $guestProduct->id,
      'product_variant_id' => null,
      'quantity' => 1,
      'unit_price' => $guestProduct->price,
    ]);
  }
}
