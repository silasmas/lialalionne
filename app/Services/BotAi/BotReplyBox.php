<?php

namespace App\Services\BotAi;

use Illuminate\Support\Facades\Cache;

/**
 * Boîte des réponses de l'IA en attente de livraison, par numéro.
 *
 * Le job BotAiReplyJob y dépose la réponse ; le webhook « Résultat » du
 * flux Callbell (POST /api/bot/v1/resultat) vient la chercher, en attendant
 * quelques secondes si l'IA travaille encore. Aucune dépendance à l'API
 * Callbell : le message part par le flux, comme une réponse normale.
 */
class BotReplyBox
{
  private const TTL_SECONDS = 1800;

  private const BUSY_SECONDS = 180;

  /**
   * Signale qu'une réponse est en préparation pour ce numéro.
   *
   * @param string $phone Numéro WhatsApp
   * @return void
   */
  public function busy(string $phone): void
  {
    Cache::put($this->key('busy', $phone), true, self::BUSY_SECONDS);
  }

  /**
   * @param string $phone Numéro WhatsApp
   * @return void
   */
  public function done(string $phone): void
  {
    Cache::forget($this->key('busy', $phone));
  }

  /**
   * @param string $phone Numéro WhatsApp
   * @return bool True si l'IA travaille encore pour ce numéro
   */
  public function isBusy(string $phone): bool
  {
    return (bool) Cache::get($this->key('busy', $phone), false);
  }

  /**
   * Dépose une réponse.
   *
   * @param string $phone Numéro WhatsApp
   * @param string $state ia ou rx_humain
   * @param string $text Texte WhatsApp
   * @return void
   */
  public function put(string $phone, string $state, string $text): void
  {
    $replies = Cache::get($this->key('replies', $phone), []);
    $replies[] = ['etat' => $state, 'text' => $text];

    Cache::put($this->key('replies', $phone), $replies, self::TTL_SECONDS);
  }

  /**
   * Retire toutes les réponses en attente, regroupées en un seul message.
   *
   * @param string $phone Numéro WhatsApp
   * @return array{etat: string, text: string}|null Réponse ou null si rien
   */
  public function take(string $phone): ?array
  {
    $replies = Cache::pull($this->key('replies', $phone), []);

    if ($replies === []) {
      return null;
    }

    $human = collect($replies)->contains('etat', BotAiAgent::STATE_HUMAN);

    return [
      'etat' => $human ? BotAiAgent::STATE_HUMAN : BotAiAgent::STATE_AI,
      'text' => collect($replies)->pluck('text')->filter()->implode("\n\n"),
    ];
  }

  /**
   * @param string $type busy ou replies
   * @param string $phone Numéro WhatsApp
   * @return string Clé de cache
   */
  private function key(string $type, string $phone): string
  {
    return 'bot-ai-' . $type . ':' . preg_replace('/\D+/', '', $phone);
  }
}
