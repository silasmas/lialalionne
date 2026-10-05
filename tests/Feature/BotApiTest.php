<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests de l'API du bot WhatsApp et du lien de paiement /payer/{token}.
 */
class BotApiTest extends TestCase
{
  use RefreshDatabase;

  private const KEY = 'test-bot-key';

  /** @var array<string, Product> */
  private array $products = [];

  /**
   * @return void
   */
  protected function setUp(): void
  {
    parent::setUp();

    config([
      'bot.api_key' => self::KEY,
      'bot.kit_discount_percent' => 0,
      'bot.notify_url' => null,
      'services.flexpay.merchant' => null,
      'services.flexpay.token' => null,
    ]);

    $category = Category::query()->create([
      'name' => 'Soin fessier',
      'slug' => 'soin-fessier',
      'is_active' => true,
    ]);

    foreach ([
      ['CL-FES-0002', 'Crème Bio Maca Vitesse++', 22],
      ['CL-FES-0003', 'Boule de Neige', 20],
      ['CL-MIN-0001', 'La Purge Fessier', 18],
      ['CL-SOLO-001', 'Produit seul', 10],
    ] as [$sku, $name, $price]) {
      $this->products[$sku] = Product::query()->create([
        'category_id' => $category->id,
        'name' => $name,
        'slug' => \Illuminate\Support\Str::slug($name),
        'sku' => $sku,
        'short_description' => 'Description de ' . $name,
        'price' => $price,
        'stock' => 10,
        'track_stock' => true,
        'is_active' => true,
      ]);
    }

    $this->products['CL-FES-0002']->syncRangeCompanions([
      $this->products['CL-FES-0003']->id,
      $this->products['CL-MIN-0001']->id,
    ]);
  }

  /**
   * @return array<string, string> En-têtes authentifiés
   */
  private function headers(): array
  {
    return ['X-Bot-Key' => self::KEY, 'Accept' => 'application/json'];
  }

  /**
   * @return void
   */
  public function testRejectsMissingOrWrongKey(): void
  {
    $this->getJson('/api/bot/catalogue')->assertStatus(401);
    $this->getJson('/api/bot/catalogue', ['X-Bot-Key' => 'faux'])->assertStatus(401);

    config(['bot.api_key' => null]);
    $this->getJson('/api/bot/catalogue', $this->headers())->assertStatus(503);
  }

  /**
   * @return void
   */
  public function testCatalogueReturnsPricesInBothCurrencies(): void
  {
    $response = $this->getJson('/api/bot/catalogue?q=Maca', $this->headers())->assertOk();

    $response->assertJsonPath('count', 1)
      ->assertJsonPath('products.0.sku', 'CL-FES-0002')
      ->assertJsonPath('products.0.price.cdf', 62700)
      ->assertJsonPath('products.0.in_stock', true);

    $this->getJson('/api/bot/produits/CL-FES-0002', $this->headers())
      ->assertOk()
      ->assertJsonCount(2, 'product.routine_companions');

    $this->getJson('/api/bot/produits/inconnu', $this->headers())->assertNotFound();
  }

  /**
   * @return void
   */
  public function testRoutinesGroupLinkedProductsWithKitPrice(): void
  {
    config(['bot.kit_discount_percent' => 10]);

    $response = $this->getJson('/api/bot/routines', $this->headers())->assertOk();

    $response->assertJsonCount(1, 'routines')
      ->assertJsonCount(3, 'routines.0.products')
      ->assertJsonPath('routines.0.price_separately.cdf', 171000)
      ->assertJsonPath('routines.0.kit_price.cdf', 153900);
  }

  /**
   * @return void
   */
  public function testRecognisesCustomerWhateverThePhoneFormat(): void
  {
    $this->getJson('/api/bot/clientes/243812345678', $this->headers())
      ->assertOk()
      ->assertJsonPath('known', false);

    User::factory()->create(['name' => 'Grace M.', 'phone' => '081 234 5678']);

    $this->getJson('/api/bot/clientes/+243812345678', $this->headers())
      ->assertOk()
      ->assertJsonPath('known', true)
      ->assertJsonPath('customer.name', 'Grace M.')
      ->assertJsonPath('customer.phone', '+243812345678');
  }

  /**
   * @return void
   */
  public function testQuoteAddsShippingAndKitDiscount(): void
  {
    config(['bot.kit_discount_percent' => 10]);

    $response = $this->postJson('/api/bot/devis', [
      'items' => [
        ['sku' => 'CL-FES-0002'],
        ['sku' => 'CL-FES-0003'],
        ['sku' => 'CL-MIN-0001'],
      ],
      'fulfillment_type' => 'pickup',
      'currency' => 'CDF',
    ], $this->headers())->assertOk();

    $response->assertJsonPath('quote.subtotal.value', 171000)
      ->assertJsonPath('quote.shipping.value', 0)
      ->assertJsonPath('quote.discount.value', 17100)
      ->assertJsonPath('quote.total.value', 153900);

    $this->postJson('/api/bot/devis', [
      'items' => [['sku' => 'CL-SOLO-001', 'quantity' => 2]],
      'fulfillment_type' => 'delivery',
      'currency' => 'CDF',
    ], $this->headers())
      ->assertOk()
      ->assertJsonPath('quote.discount.value', 0)
      ->assertJsonPath('quote.shipping.value', 12825);
  }

  /**
   * @return void
   */
  public function testCreatesWhatsappOrderWithPaymentLink(): void
  {
    $response = $this->postJson('/api/bot/commandes', [
      'phone' => '0991234567',
      'name' => 'Amina Nsenga',
      'items' => [['sku' => 'CL-SOLO-001', 'quantity' => 2]],
      'fulfillment_type' => 'delivery',
      'address' => '12 avenue des Fleurs',
      'commune' => 'Gombe',
      'payment_method' => 'mobile_money',
      'currency' => 'CDF',
    ], $this->headers())->assertCreated();

    $number = $response->json('order.order_number');
    $order = Order::query()->where('order_number', $number)->firstOrFail();

    $this->assertSame('whatsapp', $order->source);
    $this->assertSame(OrderStatus::Pending, $order->status);
    $this->assertNotNull($order->payment_token);
    $this->assertSame('+243991234567', $order->user->phone);
    $this->assertSame(0, Cart::query()->count(), 'Le panier temporaire du bot doit être supprimé.');
    $this->assertStringContainsString('/payer/' . $order->payment_token, (string) $response->json('order.card_payment_url'));
    $response->assertJsonPath('order.total_value', 69825);

    $this->getJson("/api/bot/commandes/{$number}?phone=243991234567", $this->headers())
      ->assertOk()
      ->assertJsonPath('order.status', 'pending');

    $this->getJson("/api/bot/commandes/{$number}?phone=243810000000", $this->headers())
      ->assertNotFound();
  }

  /**
   * @return void
   */
  public function testRequiresNameForNewCustomerAndAddressForDelivery(): void
  {
    $this->postJson('/api/bot/commandes', [
      'phone' => '0991234567',
      'items' => [['sku' => 'CL-SOLO-001']],
      'fulfillment_type' => 'pickup',
      'payment_method' => 'mobile_money',
    ], $this->headers())->assertStatus(422)->assertJsonValidationErrors('name');

    $this->postJson('/api/bot/commandes', [
      'phone' => '0991234567',
      'name' => 'Amina',
      'items' => [['sku' => 'CL-SOLO-001']],
      'fulfillment_type' => 'delivery',
      'payment_method' => 'mobile_money',
    ], $this->headers())->assertStatus(422)->assertJsonValidationErrors('address');
  }

  /**
   * @return void
   */
  public function testCashOnDeliveryOrderGoesStraightToProcessing(): void
  {
    app(SiteSettingsService::class)->setMany(['payment_cod_enabled' => true]);

    $response = $this->postJson('/api/bot/commandes', [
      'phone' => '0851234567',
      'name' => 'Grace',
      'items' => [['sku' => 'CL-SOLO-001']],
      'fulfillment_type' => 'pickup',
      'payment_method' => 'cod',
    ], $this->headers())->assertCreated();

    $response->assertJsonPath('order.status', OrderStatus::Processing->value)
      ->assertJsonPath('order.card_payment_url', null);
  }

  /**
   * @return void
   */
  public function testMobileMoneyPushFromWhatsappAndCardLinkPage(): void
  {
    $number = $this->postJson('/api/bot/commandes', [
      'phone' => '0821234567',
      'name' => 'Marie K.',
      'items' => [['sku' => 'CL-SOLO-001']],
      'fulfillment_type' => 'pickup',
      'payment_method' => 'mobile_money',
    ], $this->headers())->json('order.order_number');

    $order = Order::query()->where('order_number', $number)->firstOrFail();

    $link = $this->postJson("/api/bot/commandes/{$number}/lien-carte", ['phone' => '0821234567'], $this->headers())
      ->assertOk()
      ->json('card_payment_url');
    $this->assertStringContainsString('/payer/' . $order->payment_token, $link);

    $this->get('/payer/' . $order->payment_token)
      ->assertOk()
      ->assertSee($number)
      ->assertSee('Payer par carte bancaire')
      ->assertDontSee('Mobile Money</h1>', false);

    $this->get('/payer/' . str_repeat('x', 48))->assertOk()->assertSee('Lien introuvable');

    $this->postJson("/api/bot/commandes/{$number}/mobile-money", ['phone' => '0810000000'], $this->headers())
      ->assertNotFound();

    $this->postJson("/api/bot/commandes/{$number}/mobile-money", [
      'phone' => '0821234567',
      'payer_phone' => '0991234567',
    ], $this->headers())
      ->assertOk()
      ->assertJsonPath('operator', 'Airtel Money')
      ->assertJsonPath('payer_phone', '+243991234567')
      ->assertJsonPath('paid', true);

    $order->refresh();
    $this->assertSame(OrderStatus::Paid, $order->status);
    $this->assertSame(PaymentStatus::Paid, $order->payment->status);

    $this->postJson("/api/bot/commandes/{$number}/verifier", ['phone' => '0821234567'], $this->headers())
      ->assertOk()
      ->assertJsonPath('paid', true);

    $this->get('/payer/' . $order->payment_token)->assertOk()->assertSee('est confirmée');
    $this->post('/payer/' . $order->payment_token . '/carte')->assertStatus(410);

    $this->postJson("/api/bot/commandes/{$number}/mobile-money", ['phone' => '0821234567'], $this->headers())
      ->assertStatus(422);
  }

  /**
   * @return void
   */
  public function testAcceptsBotTokenInQueryStringLikeCallbell(): void
  {
    $this->getJson('/api/bot/catalogue?bot_token=' . self::KEY)->assertOk();
  }

  /**
   * @return void
   */
  public function testSendsWhatsappMessageThroughCallbellWhenOrderIsPaid(): void
  {
    config(['services.callbell.token' => 'cb-token', 'services.callbell.channel_uuid' => 'chan-1']);
    Http::fake(['api.callbell.eu/*' => Http::response(['message' => ['uuid' => 'x']])]);

    $number = $this->postJson('/api/bot/commandes', [
      'phone' => '0821234567',
      'name' => 'Marie K.',
      'items' => [['sku' => 'CL-SOLO-001']],
      'fulfillment_type' => 'pickup',
      'payment_method' => 'mobile_money',
    ], $this->headers())->json('order.order_number');

    Order::query()->where('order_number', $number)->firstOrFail()->update(['status' => OrderStatus::Paid]);

    Http::assertSent(fn ($request) => $request->url() === 'https://api.callbell.eu/v1/messages/send'
      && $request['to'] === '+243821234567'
      && $request['channel_uuid'] === 'chan-1'
      && str_contains($request['content']['text'], $number)
      && str_contains($request['content']['text'], 'Paiement'));
  }

  /**
   * @return void
   */
  public function testExpiredPaymentLinkIsRefused(): void
  {
    $number = $this->postJson('/api/bot/commandes', [
      'phone' => '0821234567',
      'name' => 'Marie K.',
      'items' => [['sku' => 'CL-SOLO-001']],
      'fulfillment_type' => 'pickup',
      'payment_method' => 'mobile_money',
    ], $this->headers())->json('order.order_number');

    $order = Order::query()->where('order_number', $number)->firstOrFail();
    $order->update(['payment_token_expires_at' => now()->subHour()]);

    $this->get('/payer/' . $order->payment_token)->assertOk()->assertSee('a expiré');
    $this->getJson("/api/bot/commandes/{$number}?phone=0821234567", $this->headers())
      ->assertJsonPath('order.card_payment_url', null);
  }

  /**
   * @return void
   */
  public function testNotifiesPlatformWhenWhatsappOrderStatusChanges(): void
  {
    config(['bot.notify_url' => 'https://bot.example.test/hook', 'bot.notify_secret' => 'secret']);
    Http::fake(['bot.example.test/*' => Http::response(['ok' => true])]);

    $number = $this->postJson('/api/bot/commandes', [
      'phone' => '0821234567',
      'name' => 'Marie K.',
      'items' => [['sku' => 'CL-SOLO-001']],
      'fulfillment_type' => 'pickup',
      'payment_method' => 'mobile_money',
    ], $this->headers())->json('order.order_number');

    Order::query()->where('order_number', $number)->firstOrFail()
      ->update(['status' => OrderStatus::Shipped]);

    Http::assertSent(function ($request) use ($number) {
      $body = $request->body();

      return $request->url() === 'https://bot.example.test/hook'
        && $request['event'] === 'order.status_changed'
        && $request['order']['order_number'] === $number
        && $request['order']['status'] === 'shipped'
        && $request['phone'] === '+243821234567'
        && $request->header('X-Bot-Signature')[0] === hash_hmac('sha256', $body, 'secret');
    });
  }
}
