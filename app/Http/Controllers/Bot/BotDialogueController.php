<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\BotAi\BotAiAgent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Point d'entrée du bot WhatsApp. Le relais SDev poste chaque message de
 * la cliente et attend la réponse de l'IA dans le même appel (jusqu'à 30 s).
 */
class BotDialogueController extends Controller
{
  /**
   * POST /api/bot/v1/dialogue  {phone, reponse, vocal?, piece_jointe?}
   *
   * Clé : en-tête X-Bot-Key (ou ?bot_token=, conservé).
   * « vocal » : transcription d'une note vocale, transmise à l'IA comme un
   * message écrit. « piece_jointe » : URL du dernier fichier reçu, pour ne
   * pas prendre cette URL pour un message.
   *
   * Répond toujours 200 : {ok, etat, text}.
   * - etat « ia » : réponse de la conseillère, à afficher telle quelle ;
   * - etat « rx_humain » : transfert à l'équipe (text contient le message
   *   de passation). Tant que la conversation reste à l'équipe, text est vide.
   *
   * @param Request $request Requête
   * @param BotAiAgent $agent Conseillère IA
   * @return JsonResponse Réponse à afficher sur WhatsApp
   */
  public function __invoke(Request $request, BotAiAgent $agent): JsonResponse
  {
    $this->repairCallbellBody($request);

    $data = $request->validate([
      'phone' => ['required', 'string', 'max:30'],
      'reponse' => ['nullable', 'string', 'max:4000'],
      'vocal' => ['nullable', 'string', 'max:4000'],
      'piece_jointe' => ['nullable', 'string', 'max:2000'],
    ]);

    $phone = $data['phone'];
    $message = $this->clean($data['reponse'] ?? null);
    $voice = $this->clean($data['vocal'] ?? null);
    $attachment = $this->clean($data['piece_jointe'] ?? null);

    // Un vocal ou une photo peut arriver avec l'URL du fichier en guise de texte.
    if ($message !== '' && ($message === $attachment || ($attachment !== '' && preg_match('#^https?://\S+$#', $message)))) {
      $message = '';
    }

    // Une transcription périmée (identique à la dernière réponse) est ignorée.
    if ($voice !== '' && ($message === '' || $message === $voice) && !$agent->isLastReply($phone, $voice)) {
      $message = '[Note vocale] ' . $voice;
    }

    return response()->json(['ok' => true] + $agent->handle($phone, $message));
  }

  /**
   * Relit un corps JSON dont les valeurs n'ont pas été échappées (guillemets
   * et retours à la ligne insérés tels quels). Conservé : le relais peut
   * encore envoyer ce format, hérité de Callbell.
   *
   * @param Request $request Requête
   * @return void
   */
  private function repairCallbellBody(Request $request): void
  {
    $raw = (string) $request->getContent();

    if ($raw === '' || $request->filled('phone') || json_decode($raw) !== null) {
      return;
    }

    $keys = 'phone|reponse|vocal|piece_jointe';

    if (!preg_match_all('/"(' . $keys . ')"\s*:\s*"/', $raw, $matches, PREG_OFFSET_CAPTURE)) {
      return;
    }

    $fields = [];
    $count = count($matches[0]);

    for ($i = 0; $i < $count; $i++) {
      $start = $matches[0][$i][1] + strlen($matches[0][$i][0]);
      $end = $i + 1 < $count ? $matches[0][$i + 1][1] : strlen($raw);
      $value = substr($raw, $start, $end - $start);
      $value = preg_replace($i + 1 < $count ? '/"\s*,\s*$/' : '/"\s*}\s*$/', '', $value) ?? $value;
      $decoded = json_decode('"' . $value . '"');

      $fields[$matches[1][$i][0]] = is_string($decoded) ? $decoded : str_replace('\\"', '"', $value);
    }

    $request->merge($fields);
  }

  /**
   * Ignore une variable vide ou non remplacée (« {{…}} »).
   *
   * @param string|null $value Valeur reçue
   * @return string Texte utile
   */
  private function clean(?string $value): string
  {
    $value = trim((string) $value);

    return preg_match('/^\{\{.*\}\}$/s', $value) ? '' : $value;
  }
}
