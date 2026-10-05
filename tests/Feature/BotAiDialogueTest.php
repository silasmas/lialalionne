<?php

namespace Tests\Feature;

use App\Models\BotConversation;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Tests de la conseillère IA (POST /api/bot/v1/dialogue) avec l'API Claude simulée.
 */
class BotAiDialogueTest extends TestCase
{
  use RefreshDatabase;

  private const KEY = 'test-bot-key';

  private const CLAUDE = 'https://api.anthropic.com/v1/messages';

  /**
   * @return void
   */
  protected function setUp(): void
  {
    parent::setUp();

    config([
      'bot.api_key' => self::KEY,
      'bot.kit_discount_percent' => 0,
      'services.anthropic.key' => 'sk-test',
      'services.flexpay.merchant' => null,
      'services.flexpay.token' => null,
    ]);

    $category = Category::query()->create(['name' => 'Soin fessier', 'slug' => 'soin-fessier', 'is_active' => true]);

    Product::query()->create([
      'category_id' => $category->id,
      'name' => 'Crème Bio Maca Vitesse++',
      'slug' => 'creme-bio-maca-vitesse',
      'sku' => 'CL-FES-0002',
      'short_description' => 'Crème fessier volumatrice.',
      'price' => 22,
      'stock' => 10,
      'track_stock' => true,
      'is_active' => true,
    ]);
  }

  /**
   * @param string $message Message de la cliente
   * @param string $phone Numéro
   * @return \Illuminate\Testing\TestResponse Réponse
   */
  private function say(string $message, string $phone = '+243 82 123 4567')
  {
    return $this->postJson('/api/bot/v1/dialogue?bot_token=' . self::KEY, [
      'phone' => $phone,
      'reponse' => $message,
    ]);
  }

  /**
   * @param string $text Texte de l'IA
   * @return array<string, mixed> Réponse Claude finale
   */
  private function text(string $text): array
  {
    return ['content' => [['type' => 'text', 'text' => $text]], 'stop_reason' => 'end_turn'];
  }

  /**
   * @param string $id Identifiant de l'appel
   * @param string $name Outil
   * @param array<string, mixed> $input Paramètres
   * @return array<string, mixed> Réponse Claude demandant un outil
   */
  private function tool(string $id, string $name, array $input = []): array
  {
    return [
      'content' => [['type' => 'tool_use', 'id' => $id, 'name' => $name, 'input' => $input ?: new \stdClass()]],
      'stop_reason' => 'tool_use',
    ];
  }

  /**
   * @return void
   */
  public function testAnswersWithRealCatalogueThroughTools(): void
  {
    Http::fake([self::CLAUDE => Http::sequence()
      ->push($this->tool('t1', 'rechercher_produits', ['recherche' => 'maca']))
      ->push($this->text('La *Crème Bio Maca Vitesse++* est à 62 700 FC 💛 Je vous la réserve ?'))]);

    $this->say('bjr mama, vous avez la crème maca ?')
      ->assertOk()
      ->assertJson(['ok' => true, 'etat' => 'ia'])
      ->assertJsonPath('text', 'La *Crème Bio Maca Vitesse++* est à 62 700 FC 💛 Je vous la réserve ?');

    Http::assertSentCount(2);

    Http::assertSent(function (Request $request) {
      $system = collect($request['system'])->pluck('text')->implode("\n");

      return $request->hasHeader('x-api-key', 'sk-test')
        && $request->hasHeader('anthropic-version', '2023-06-01')
        && str_contains($system, 'conseillère beauté')
        && str_contains($system, 'CL-FES-0002 | Crème Bio Maca Vitesse++')
        && str_contains($system, '+243821234567')
        && count($request['tools']) === 12;
    });

    Http::assertSent(function (Request $request) {
      $last = collect($request['messages'])->last();

      return $last['role'] === 'user'
        && is_array($last['content'])
        && $last['content'][0]['type'] === 'tool_result'
        && str_contains($last['content'][0]['content'], '62700');
    });

    $conversation = BotConversation::query()->where('phone', '243821234567')->firstOrFail();
    $this->assertCount(4, $conversation->history);
    $this->assertSame(['user', 'tool', 'assistant'], $conversation->messages()->orderBy('id')->pluck('role')->all());
  }

  /**
   * @return void
   */
  public function testRefusesOrderWithoutQuoteThenCreatesItAfterQuote(): void
  {
    $items = [['sku' => 'CL-FES-0002', 'quantity' => 1]];
    $order = ['items' => $items, 'name' => 'Grace', 'fulfillment_type' => 'pickup', 'payment_method' => 'mobile_money'];

    Http::fake([self::CLAUDE => Http::sequence()
      ->push($this->tool('t1', 'creer_commande', $order))
      ->push($this->text('Je vous prépare d\'abord le récapitulatif.'))
      ->push($this->tool('t2', 'calculer_devis', ['items' => $items, 'fulfillment_type' => 'pickup']))
      ->push($this->text('Total : 62 700 FC. On valide ?'))
      ->push($this->tool('t3', 'creer_commande', $order))
      ->push($this->text('Commande créée ✅'))]);

    $this->say('je prends la crème, retrait en boutique')->assertJsonPath('etat', 'ia');
    $this->assertSame(0, Order::query()->count());

    Http::assertSent(function (Request $request) {
      $last = collect($request['messages'])->last();

      return is_array($last['content'])
        && ($last['content'][0]['type'] ?? null) === 'tool_result'
        && $last['content'][0]['is_error'] === true
        && str_contains($last['content'][0]['content'], 'dernier devis');
    });

    $this->say('combien au total ?')->assertJsonPath('text', 'Total : 62 700 FC. On valide ?');
    $this->say('oui')->assertJsonPath('text', 'Commande créée ✅');

    $created = Order::query()->firstOrFail();
    $this->assertSame('whatsapp', $created->source);
    $this->assertSame('+243821234567', $created->user->phone);
    $this->assertSame($created->user_id, BotConversation::query()->first()->user_id);
  }

  /**
   * @return void
   */
  public function testHandoffToHumanStopsTheAi(): void
  {
    Http::fake([self::CLAUDE => Http::sequence()
      ->push($this->tool('t1', 'transferer_humain', ['raison' => 'Réclamation colis abîmé']))
      ->push($this->text('Je transmets à une conseillère, elle vous répond très vite 💛'))]);

    $this->say('mon colis est arrivé abîmé, je suis pas contente')
      ->assertJsonPath('etat', 'rx_humain')
      ->assertJsonPath('text', 'Je transmets à une conseillère, elle vous répond très vite 💛');

    $this->say('allô ?')->assertJsonPath('etat', 'rx_humain');

    Http::assertSentCount(2);
    $this->assertSame('Réclamation colis abîmé', BotConversation::query()->first()->handoff_reason);
  }

  /**
   * @return void
   */
  public function testFallsBackToHumanWithoutKeyOrOnApiError(): void
  {
    config(['services.anthropic.key' => null]);
    $this->say('bonjour')->assertOk()->assertJsonPath('etat', 'rx_humain');

    config(['services.anthropic.key' => 'sk-test']);
    Http::fake([self::CLAUDE => Http::response(['error' => 'overloaded'], 529)]);

    $this->say('bonjour', '0991234567')->assertOk()->assertJsonPath('etat', 'rx_humain');
    $this->assertStringContainsString('529', (string) BotConversation::query()->where('phone', '243991234567')->first()->handoff_reason);
  }

  /**
   * @return void
   */
  public function testConversationRestartsAfterLongPause(): void
  {
    BotConversation::query()->create([
      'phone' => '243821234567',
      'status' => BotConversation::STATUS_HUMAN,
      'history' => [['role' => 'user', 'content' => 'ancien message']],
      'last_message_at' => now()->subDays(2),
    ]);

    Http::fake([self::CLAUDE => Http::response($this->text('Bonjour ! Que puis-je faire pour vous ? 💛'))]);

    $this->say('bonjour')->assertJsonPath('etat', 'ia');

    Http::assertSent(fn (Request $request) => count($request['messages']) === 1);
  }

  /**
   * @return void
   */
  public function testRejectsWrongToken(): void
  {
    $this->postJson('/api/bot/v1/dialogue?bot_token=faux', ['phone' => '0821234567', 'reponse' => 'x'])
      ->assertStatus(401);
  }
}
