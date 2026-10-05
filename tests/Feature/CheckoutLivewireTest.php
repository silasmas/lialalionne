<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Livewire\Shop\CheckoutPage;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests Livewire du checkout (panier vide, commande COD).
 */
class CheckoutLivewireTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie que le checkout redirige vers le panier s'il est vide.
   *
   * @return void
   */
  public function testCheckoutRedirectsToCartWhenEmpty(): void
  {
    Livewire::test(CheckoutPage::class)
      ->assertRedirect(route('shop.cart'));
  }

  /**
   * Vérifie qu'un client connecté peut passer commande en retrait + COD.
   *
   * @return void
   */
  public function testAuthenticatedUserCanPlacePickupCodOrder(): void
  {
    app(SiteSettingsService::class)->setMany([
      'payment_cod_enabled' => true,
      'pickup_in_store_enabled' => true,
    ]);

    $user = User::factory()->create([
      'name' => 'Marie Dupont',
      'email' => 'marie.checkout@example.com',
      'phone' => '+243810000001',
      'is_admin' => false,
    ]);

    $product = $this->createProduct(18.50);
    $cart = app(CartService::class)->getOrCreateCart($user);
    app(CartService::class)->addItem($cart, $product, 1);

    Livewire::actingAs($user)
      ->test(CheckoutPage::class)
      ->set('firstName', 'Marie')
      ->set('lastName', 'Dupont')
      ->set('email', 'marie.checkout@example.com')
      ->set('phone', '+243810000001')
      ->set('fulfillmentType', 'pickup')
      ->set('paymentMethod', PaymentMethod::Cod->value)
      ->set('currency', 'CDF')
      ->call('placeOrder')
      ->assertHasNoErrors()
      ->assertRedirect();

    $order = Order::query()->where('user_id', $user->id)->first();

    $this->assertNotNull($order);
    $this->assertSame(PaymentMethod::Cod, $order->payment_method);
    $this->assertSame('pickup', $order->fulfillment_type);
    $this->assertSame(OrderStatus::Processing, $order->status);
    $this->assertSame(PaymentStatus::Pending, $order->payment->status);
  }

  /**
   * Vérifie que le checkout refuse une méthode de paiement désactivée.
   *
   * @return void
   */
  public function testCheckoutRejectsDisabledPaymentMethod(): void
  {
    app(SiteSettingsService::class)->setMany([
      'payment_cod_enabled' => false,
      'pickup_in_store_enabled' => true,
    ]);

    $user = User::factory()->create([
      'email' => 'marie.checkout@example.com',
      'phone' => '+243810000001',
      'is_admin' => false,
    ]);

    $product = $this->createProduct();
    $cart = app(CartService::class)->getOrCreateCart($user);
    app(CartService::class)->addItem($cart, $product, 1);

    Livewire::actingAs($user)
      ->test(CheckoutPage::class)
      ->set('firstName', 'Marie')
      ->set('lastName', 'Dupont')
      ->set('email', 'marie.checkout@example.com')
      ->set('phone', '+243810000001')
      ->set('fulfillmentType', 'pickup')
      ->set('paymentMethod', PaymentMethod::Cod->value)
      ->set('currency', 'CDF')
      ->call('placeOrder')
      ->assertHasErrors(['paymentMethod']);

    $this->assertSame(0, Order::query()->count());
  }

  /**
   * Crée un produit minimal pour le checkout.
   *
   * @param float $price Prix catalogue EUR
   * @return Product Produit créé
   */
  private function createProduct(float $price = 10.0): Product
  {
    $category = Category::query()->create([
      'name' => 'Test',
      'slug' => 'test-checkout',
      'description' => 'Catégorie test',
      'is_active' => true,
      'sort_order' => 1,
    ]);

    return Product::query()->create([
      'category_id' => $category->id,
      'name' => 'Produit checkout',
      'slug' => 'produit-checkout',
      'sku' => 'CHK-001',
      'short_description' => 'Test',
      'description' => 'Test',
      'price' => $price,
      'stock' => 50,
      'track_stock' => true,
      'is_active' => true,
      'is_featured' => false,
      'weight' => 100,
    ]);
  }
}
