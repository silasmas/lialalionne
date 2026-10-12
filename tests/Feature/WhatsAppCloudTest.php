<?php

namespace Tests\Feature;

use App\Services\WhatsAppCloud;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * Envoi WhatsApp via l'API Cloud de Meta (graph.facebook.com).
 */
class WhatsAppCloudTest extends TestCase
{
  /**
   * @return void
   */
  protected function setUp(): void
  {
    parent::setUp();

    config([
      'services.whatsapp.token' => 'meta-token',
      'services.whatsapp.phone_number_id' => '123456789',
    ]);
  }

  /**
   * @return void
   */
  public function testSendsATextMessageWithDigitsOnly(): void
  {
    Http::fake([
      'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.1']]]),
    ]);

    $sent = app(WhatsAppCloud::class)->sendText('+243 82 123 4567', 'Bonjour');

    $this->assertTrue($sent);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://graph.facebook.com/v21.0/123456789/messages'
      && $request->hasHeader('Authorization', 'Bearer meta-token')
      && $request['to'] === '243821234567'
      && $request['type'] === 'text'
      && $request['text']['body'] === 'Bonjour'
      && $request['text']['preview_url'] === true);
  }

  /**
   * @return void
   */
  public function testLogsWhenThe24HourWindowIsClosed(): void
  {
    $logged = [];

    Log::listen(function (MessageLogged $event) use (&$logged): void {
      $logged[] = $event->message;
    });

    Http::fake([
      'graph.facebook.com/*' => Http::response([
        'error' => ['message' => 'Re-engagement message', 'code' => 131047],
      ], 400),
    ]);

    $sent = app(WhatsAppCloud::class)->sendText('0821234567', 'Votre commande est prête');

    $this->assertFalse($sent);
    $this->assertTrue(collect($logged)->contains(fn (string $message): bool => str_contains($message, '131047')));
  }

  /**
   * @return void
   */
  public function testSendTemplatePostsAMetaTemplate(): void
  {
    Http::fake([
      'graph.facebook.com/*' => Http::response(['messages' => [['id' => 'wamid.2']]]),
    ]);

    $sent = app(WhatsAppCloud::class)->sendTemplate('243821234567', 'suivi_commande', 'fr', ['LL-1', 'prête']);

    $this->assertTrue($sent);

    Http::assertSent(fn (Request $request) => $request['type'] === 'template'
      && $request['template']['name'] === 'suivi_commande'
      && $request['template']['language']['code'] === 'fr'
      && $request['template']['components'][0]['parameters'][0]['text'] === 'LL-1'
      && $request['template']['components'][0]['parameters'][1]['text'] === 'prête');
  }

  /**
   * @return void
   */
  public function testANetworkErrorDoesNotThrow(): void
  {
    Http::fake(function (): void {
      throw new ConnectionException('timeout');
    });

    $sent = app(WhatsAppCloud::class)->sendText('0821234567', 'Bonjour');

    $this->assertFalse($sent);
  }
}
