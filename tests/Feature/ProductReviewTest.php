<?php

namespace Tests\Feature;

use App\Livewire\Shop\ProductShow;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Tests des avis clients sur les fiches produit.
 */
class ProductReviewTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Crée un produit actif de test.
   *
   * @return Product Produit créé
   */
  private function makeProduct(): Product
  {
    $category = Category::query()->create([
      'name' => 'Test',
      'slug' => 'test',
      'is_active' => true,
      'sort_order' => 1,
    ]);

    return Product::query()->create([
      'category_id' => $category->id,
      'name' => 'Produit test',
      'slug' => 'produit-test',
      'sku' => 'TEST-001',
      'price' => 10,
      'stock' => 10,
      'track_stock' => true,
      'is_active' => true,
    ]);
  }

  /**
   * Vérifie qu'un client connecté peut soumettre un avis, non publié par défaut.
   *
   * @return void
   */
  public function testAuthenticatedUserCanSubmitReviewPendingModeration(): void
  {
    $user = User::factory()->create();
    $product = $this->makeProduct();

    Livewire::actingAs($user)
      ->test(ProductShow::class, ['product' => $product])
      ->set('reviewRating', 4)
      ->set('reviewComment', 'Très bon produit, je recommande.')
      ->call('submitReview');

    $this->assertDatabaseHas('reviews', [
      'product_id' => $product->id,
      'user_id' => $user->id,
      'rating' => 4,
      'is_approved' => false,
    ]);
  }

  /**
   * Vérifie qu'un client ne peut pas laisser deux avis sur le même produit.
   *
   * @return void
   */
  public function testUserCannotReviewSameProductTwice(): void
  {
    $user = User::factory()->create();
    $product = $this->makeProduct();

    Review::query()->create([
      'product_id' => $product->id,
      'user_id' => $user->id,
      'rating' => 5,
      'comment' => 'Premier avis',
      'is_approved' => true,
    ]);

    Livewire::actingAs($user)
      ->test(ProductShow::class, ['product' => $product])
      ->set('reviewRating', 2)
      ->set('reviewComment', 'Deuxième avis')
      ->call('submitReview');

    $this->assertSame(1, Review::query()->where('product_id', $product->id)->count());
  }

  /**
   * Vérifie que la note moyenne n'intègre que les avis approuvés.
   *
   * @return void
   */
  public function testAverageRatingOnlyCountsApprovedReviews(): void
  {
    $product = $this->makeProduct();

    Review::query()->create([
      'product_id' => $product->id,
      'user_id' => User::factory()->create()->id,
      'rating' => 5,
      'is_approved' => true,
    ]);

    Review::query()->create([
      'product_id' => $product->id,
      'user_id' => User::factory()->create()->id,
      'rating' => 1,
      'is_approved' => false,
    ]);

    $this->assertSame(5.0, $product->fresh()->averageRating());
    $this->assertSame(1, $product->fresh()->reviewsCount());
  }
}
