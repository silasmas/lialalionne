<?php

namespace Tests\Feature;

use App\Models\BotConversation;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Bouton « Commander sur WhatsApp » et écran admin des conversations IA.
 */
class BotAdminAndWhatsappButtonTest extends TestCase
{
  use RefreshDatabase;

  /**
   * @return void
   */
  public function testWhatsappLinksUseShopNumber(): void
  {
    $settings = app(SiteSettingsService::class);
    $this->assertNull($settings->whatsappUrl('x'));
    $this->assertFalse($settings->isWhatsappButtonEnabled());

    $settings->setMany(['whatsapp_number' => '081 234 5678']);

    $this->assertSame('https://wa.me/243812345678?text=Bonjour%20Lia', $settings->whatsappUrl('Bonjour Lia'));
    $this->assertTrue($settings->isWhatsappButtonEnabled());

    $category = Category::query()->create(['name' => 'Soin fessier', 'slug' => 'soin-fessier', 'is_active' => true]);
    $product = Product::query()->create([
      'category_id' => $category->id,
      'name' => 'Crème Bio Maca',
      'slug' => 'creme-bio-maca',
      'sku' => 'CL-FES-0002',
      'price' => 22,
      'stock' => 5,
      'track_stock' => true,
      'is_active' => true,
    ]);

    User::factory()->create(['is_admin' => true]);

    $this->get(route('products.show', $product))
      ->assertOk()
      ->assertSee('Commander sur WhatsApp')
      ->assertSee('https://wa.me/243812345678?text=', false)
      ->assertSee('lia-whatsapp-float', false);
  }

  /**
   * @return void
   */
  public function testAdminCanReviewBotConversations(): void
  {
    $admin = User::factory()->create(['is_admin' => true]);

    $conversation = BotConversation::query()->create([
      'phone' => '243821234567',
      'status' => BotConversation::STATUS_HUMAN,
      'handoff_reason' => 'Réclamation colis abîmé',
      'last_message_at' => now(),
    ]);
    $conversation->log('user', 'mon colis est abîmé');

    $this->actingAs($admin)
      ->get('/admin/bot-conversations')
      ->assertOk()
      ->assertSee('+243821234567')
      ->assertSee('Réclamation colis abîmé');
  }
}
