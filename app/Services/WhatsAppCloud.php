<?php

namespace App\Services;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Envoie des messages WhatsApp via l'API Cloud de Meta.
 *
 * Un texte libre n'est délivré que dans les 24 h qui suivent le dernier
 * message de la cliente (code 131047 au-delà). Les erreurs sont
 * journalisées et n'interrompent jamais la commande ni le paiement.
 */
class WhatsAppCloud
{
  private const GRAPH_VERSION = 'v21.0';

  private const WINDOW_CLOSED_CODE = 131047;

  /**
   * @param MobileMoneyService $phones Normalisation des numéros (243…)
   */
  public function __construct(private readonly MobileMoneyService $phones)
  {
  }

  /**
   * Indique si le jeton et l'identifiant de numéro Meta sont renseignés.
   *
   * @return bool True si l'envoi est possible
   */
  public function isConfigured(): bool
  {
    return filled(config('services.whatsapp.token'))
      && filled(config('services.whatsapp.phone_number_id'));
  }

  /**
   * Envoie un message texte libre (fenêtre de 24 h).
   *
   * @param string $number Numéro de la cliente, tous formats
   * @param string $text Texte WhatsApp
   * @return bool True si Meta a accepté le message
   */
  public function sendText(string $number, string $text): bool
  {
    $to = $this->recipient($number);

    if ($to === '' || trim($text) === '') {
      return false;
    }

    return $this->post($to, [
      'messaging_product' => 'whatsapp',
      'to' => $to,
      'type' => 'text',
      'text' => [
        'body' => $text,
        'preview_url' => true,
      ],
    ]);
  }

  /**
   * Envoie un modèle approuvé par Meta, pour écrire hors fenêtre de 24 h.
   * Prête, non branchée : aucun appelant tant qu'un modèle n'est pas validé.
   *
   * @param string $to Numéro destinataire, tous formats
   * @param string $name Nom du modèle Meta
   * @param string $lang Code langue (ex. fr)
   * @param array<int, string> $params Variables du corps, dans l'ordre
   * @return bool True si Meta a accepté le message
   */
  public function sendTemplate(string $to, string $name, string $lang, array $params): bool
  {
    $recipient = $this->recipient($to);

    if ($recipient === '' || trim($name) === '') {
      return false;
    }

    $template = [
      'name' => $name,
      'language' => ['code' => $lang !== '' ? $lang : 'fr'],
    ];

    if ($params !== []) {
      $template['components'] = [[
        'type' => 'body',
        'parameters' => array_map(
          fn (string $value): array => ['type' => 'text', 'text' => $value],
          array_values($params)
        ),
      ]];
    }

    return $this->post($recipient, [
      'messaging_product' => 'whatsapp',
      'to' => $recipient,
      'type' => 'template',
      'template' => $template,
    ]);
  }

  /**
   * Réduit un numéro aux chiffres, avec l'indicatif 243.
   *
   * @param string $number Numéro brut
   * @return string Destinataire Meta (243…) ou chaîne vide
   */
  private function recipient(string $number): string
  {
    return $this->phones->normalizePhone($number);
  }

  /**
   * Appel HTTP commun. Ne lève jamais d'exception.
   *
   * @param string $to Destinataire déjà normalisé
   * @param array<string, mixed> $payload Corps attendu par Meta
   * @return bool True si l'envoi est accepté
   */
  private function post(string $to, array $payload): bool
  {
    if (!$this->isConfigured()) {
      Log::info('WhatsApp Cloud non configuré : message non envoyé', [
        'to' => $to,
        'type' => $payload['type'] ?? null,
      ]);

      return false;
    }

    $phoneNumberId = trim((string) config('services.whatsapp.phone_number_id'));
    $url = 'https://graph.facebook.com/' . self::GRAPH_VERSION . '/' . $phoneNumberId . '/messages';

    try {
      $response = Http::withToken((string) config('services.whatsapp.token'))
        ->acceptJson()
        ->connectTimeout(5)
        ->timeout(15)
        ->post($url, $payload);

      if ($response->successful()) {
        return true;
      }

      $this->logRefusal($to, $response);
    } catch (\Throwable $exception) {
      Log::error('WhatsApp Cloud : exception lors de l\'envoi', [
        'to' => $to,
        'error' => $exception->getMessage(),
      ]);
    }

    return false;
  }

  /**
   * Journalise un refus Meta. Le code 131047 signifie que la fenêtre de
   * 24 h est fermée : un modèle approuvé sera nécessaire.
   *
   * @param string $to Destinataire
   * @param Response $response Réponse HTTP Meta
   * @return void
   */
  private function logRefusal(string $to, Response $response): void
  {
    $code = (int) data_get($response->json(), 'error.code', 0);

    if ($code === self::WINDOW_CLOSED_CODE) {
      Log::warning('WhatsApp Cloud : fenêtre de 24 h fermée (code 131047), un modèle approuvé est nécessaire', [
        'to' => $to,
        'body' => $response->json(),
      ]);

      return;
    }

    Log::warning('WhatsApp Cloud : envoi refusé', [
      'to' => $to,
      'status' => $response->status(),
      'code' => $code,
      'body' => $response->json(),
    ]);
  }
}
