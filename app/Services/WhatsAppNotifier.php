<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envoie des messages WhatsApp aux clientes via l'API Callbell
 * (POST /v1/messages/send), comme pour le bot Rachoux Traiteur.
 *
 * Un message libre n'est délivré que dans les 24 h qui suivent le dernier
 * message de la cliente ; au-delà, WhatsApp impose un modèle validé par Meta.
 * Sans CALLBELL_API_TOKEN / CALLBELL_CHANNEL_UUID, rien n'est envoyé : le
 * message est seulement journalisé.
 */
class WhatsAppNotifier
{
  /**
   * @param MobileMoneyService $phones Normalisation des numéros
   */
  public function __construct(private readonly MobileMoneyService $phones)
  {
  }

  /**
   * @return bool True si les identifiants Callbell sont renseignés
   */
  public function isConfigured(): bool
  {
    return filled(config('services.callbell.token')) && filled(config('services.callbell.channel_uuid'));
  }

  /**
   * Envoie un message texte libre (fenêtre de 24 h).
   *
   * @param string $number Numéro de la cliente (tous formats)
   * @param string $text Texte WhatsApp
   * @param array<string, string> $options Options Callbell (team_uuid, bot_status…)
   * @return bool True si Callbell a accepté le message
   */
  public function sendText(string $number, string $text, array $options = []): bool
  {
    return $this->send($number, [
      'type' => 'text',
      'content' => ['text' => $text],
    ] + $options);
  }

  /**
   * Envoie une image avec légende optionnelle (fenêtre de 24 h).
   *
   * @param string $number Numéro de la cliente
   * @param string $url URL publique de l'image
   * @param string|null $caption Légende
   * @return bool True si Callbell a accepté le message
   */
  public function sendImage(string $number, string $url, ?string $caption = null): bool
  {
    return $this->send($number, [
      'type' => 'image',
      'content' => array_filter(['url' => $url, 'text' => $caption], fn (?string $value): bool => filled($value)),
    ]);
  }

  /**
   * Appel HTTP commun ; ne lève jamais d'exception (le parcours métier continue).
   *
   * @param string $number Numéro de la cliente
   * @param array<string, mixed> $payload Contenu du message
   * @return bool True si envoyé
   */
  private function send(string $number, array $payload): bool
  {
    $to = '+' . $this->phones->normalizePhone($number);

    if (!$this->isConfigured()) {
      Log::info('WhatsApp (Callbell non configuré) : message non envoyé', ['to' => $to] + $payload);

      return false;
    }

    try {
      $response = Http::withToken((string) config('services.callbell.token'))
        ->connectTimeout(3)
        ->timeout(8)
        ->post(rtrim((string) config('services.callbell.url'), '/') . '/messages/send', [
          'to' => $to,
          'from' => 'whatsapp',
          'channel_uuid' => config('services.callbell.channel_uuid'),
        ] + $payload);

      if ($response->successful()) {
        return true;
      }

      Log::warning('Callbell : envoi WhatsApp refusé', [
        'to' => $to,
        'status' => $response->status(),
        'body' => $response->json(),
      ]);
    } catch (\Throwable $exception) {
      Log::error('Callbell : exception lors de l\'envoi WhatsApp', [
        'to' => $to,
        'error' => $exception->getMessage(),
      ]);
    }

    return false;
  }
}
