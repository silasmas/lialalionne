<?php

namespace Tests\Feature;

use App\Enums\CouponType;
use App\Enums\PaymentMethod;
use App\Livewire\Shop\CartPage;
use App\Livewire\Shop\CheckoutPage;
use App\Livewire\Shop\HomePage;
use App\Livewire\Shop\ProductCatalog;
use App\Livewire\Shop\ProductShow;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CartService;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests des sections merchandising, gammes, codes promo et checkout invité.
 */
class ShopMerchandisingAndGuestCheckoutTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie que l'accueil affiche les blocs dynamiques depuis les drapeaux produit.
   *
   * @return void
   */
  public function testHomePageShowsDynamicMerchandisingBlocks(): void
  {
    $this->createProduct([
      'name' => 'Huile saison',
      'slug' => 'huile-saison',
      'sku' => 'TEST-SEASON',
      'is_seasonal' => true,
      'is_new' => false,
    ]);
    $this->createProduct([
      'name' => 'Thé nouveauté',
      'slug' => 'the-nouveaute',
      'sku' => 'TEST-NEW',
      'is_new' => true,
      'compare_at_price' => 20,
    ]);

    Livewire::test(HomePage::class)
      ->assertSee('Huile saison')
      ->assertSee('Thé nouveauté')
      ->assertSee('Nouvelles tendances de saison')
      ->assertSee('Offres spéciales');
  }

  /**
   * Vérifie que la boutique filtre la nouvelle collection.
   *
   * @return void
   */
  public function testCatalogFiltersNewCollection(): void
  {
    $this->createProduct([
      'name' => 'Produit collection',
      'slug' => 'produit-collection',
      'sku' => 'TEST-COL',
      'is_new' => true,
    ]);
    $this->createProduct([
      'name' => 'Produit classique',
      'slug' => 'produit-classique',
      'sku' => 'TEST-OLD',
      'is_new' => false,
    ]);

    Livewire::withQueryParams(['selection' => 'collection'])
      ->test(ProductCatalog::class)
      ->assertSee('Produit collection')
      ->assertDontSee('Produit classique');
  }

  /**
   * Vérifie qu'une fiche affiche la gamme liée et permet de tout ajouter.
   *
   * @return void
   */
  public function testProductShowDisplaysRangeAndAddsCompanionsToCart(): void
  {
    $cream = $this->createProduct([
      'name' => 'Crème gamme',
      'slug' => 'creme-gamme',
      'sku' => 'TEST-RANGE-1',
    ]);
    $oil = $this->createProduct([
      'name' => 'Huile gamme',
      'slug' => 'huile-gamme',
      'sku' => 'TEST-RANGE-2',
    ]);
    $cream->syncRangeCompanions([$oil->id]);

    Livewire::test(ProductShow::class, ['product' => $cream])
      ->assertSee('Cette gamme')
      ->assertSee('Huile gamme')
      ->call('addRangeToCart')
      ->assertHasNoErrors();

    $cart = app(CartService::class)->getCartWithItems();

    $this->assertSame(2, $cart->items->count());
  }

  /**
   * Vérifie qu'un invité peut appliquer un code promo sur le panier.
   *
   * @return void
   */
  public function testGuestCanApplyCouponOnCart(): void
  {
    Coupon::query()->create([
      'code' => 'LIALA5',
      'name' => 'Remise 5',
      'type' => CouponType::Fixed,
      'value' => 5,
      'is_active' => true,
    ]);

    $product = $this->createProduct(['price' => 18]);
    $cart = app(CartService::class)->getOrCreateCart();
    app(CartService::class)->addItem($cart, $product, 1);

    Livewire::test(CartPage::class)
      ->set('couponCode', 'liala5')
      ->call('applyCoupon')
      ->assertHasNoErrors()
      ->assertSet('appliedCouponCode', 'LIALA5')
      ->assertSet('discountEur', 5.0);
  }

  /**
   * Vérifie qu'un invité commande avec e-mail et téléphone seulement.
   *
   * @return void
   */
  public function testGuestCanCheckoutWithEmailAndPhoneOnly(): void
  {
    app(SiteSettingsService::class)->setMany([
      'payment_cod_enabled' => true,
      'pickup_in_store_enabled' => true,
    ]);

    Coupon::query()->create([
      'code' => 'LIALA5',
      'name' => 'Remise 5',
      'type' => CouponType::Fixed,
      'value' => 5,
      'is_active' => true,
    ]);

    $product = $this->createProduct(['price' => 18]);
    $cart = app(CartService::class)->getOrCreateCart();
    app(CartService::class)->addItem($cart, $product, 1);

    Livewire::test(CheckoutPage::class)
      ->set('email', 'invite@example.com')
      ->set('phone', '+243810000099')
      ->set('fulfillmentType', 'pickup')
      ->set('paymentMethod', PaymentMethod::Cod->value)
      ->set('currency', 'CDF')
      ->set('couponCode', 'LIALA5')
      ->call('applyCoupon')
      ->assertHasNoErrors('couponCode')
      ->call('placeOrder')
      ->assertHasNoErrors()
      ->assertRedirect();

    $order = Order::query()->first();

    $this->assertNotNull($order);
    $this->assertNull($order->user_id);
    $this->assertSame('LIALA5', $order->coupon_code);
    $this->assertSame('invite@example.com', $order->payment->metadata['customer_email']);
  }

  /**
   * Vérifie que le header propose l'espace client une fois connecté.
   *
   * @return void
   */
  public function testAuthenticatedHeaderShowsAccountMenu(): void
  {
    $user = User::factory()->create([
      'is_admin' => false,
    ]);

    $this->actingAs($user)
      ->get(route('home'))
      ->assertOk()
      ->assertSee('Espace client')
      ->assertSee(route('account.dashboard'), false)
      ->assertSee(route('account.orders'), false);
  }

  /**
   * Crée un produit de test.
   *
   * @param array<string, mixed> $overrides Attributs à surcharger
   * @return Product Produit créé
   */
  private function createProduct(array $overrides = []): Product
  {
    $category = Category::query()->firstOrCreate(
      ['slug' => 'test-merch'],
      [
        'name' => 'Test merch',
        'description' => 'Catégorie test',
        'is_active' => true,
        'sort_order' => 1,
      ]
    );

    $sku = $overrides['sku'] ?? 'TEST-' . uniqid();

    return Product::query()->create(array_merge([
      'category_id' => $category->id,
      'name' => 'Produit test',
      'slug' => 'produit-test-' . uniqid(),
      'sku' => $sku,
      'short_description' => 'Test',
      'description' => 'Test',
      'price' => 15,
      'stock' => 20,
      'track_stock' => true,
      'is_active' => true,
      'is_featured' => false,
      'is_new' => false,
      'is_seasonal' => false,
      'weight' => 100,
    ], $overrides));
  }
}
