<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests du service panier (ajout article, sous-total).
 */
class CartServiceTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie l'ajout d'un produit au panier invité.
   *
   * @return void
   */
  public function testGuestCanAddProductToCart(): void
  {
    $product = $this->createProduct(price: 12.50);

    $cartService = app(CartService::class);
    $cart = $cartService->getOrCreateCart();
    $cartService->addItem($cart, $product, 2);

    $cart = $cartService->getCartWithItems();

    $this->assertCount(1, $cart->items);
    $this->assertSame(2, $cart->items->first()->quantity);
    $this->assertSame(25.0, $cart->subtotal());
  }

  /**
   * Crée un produit minimal pour les tests panier.
   *
   * @param float $price Prix catalogue EUR
   * @return Product Produit créé
   */
  private function createProduct(float $price = 10.0): Product
  {
    $category = Category::query()->create([
      'name' => 'Test',
      'slug' => 'test',
      'description' => 'Catégorie test',
      'is_active' => true,
      'sort_order' => 1,
    ]);

    return Product::query()->create([
      'category_id' => $category->id,
      'name' => 'Produit test',
      'slug' => 'produit-test',
      'sku' => 'TEST-001',
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
