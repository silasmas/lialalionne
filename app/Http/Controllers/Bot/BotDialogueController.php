<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Jobs\BotAiReplyJob;
use App\Services\BotAi\BotAiAgent;
use App\Services\BotAi\BotReplyBox;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Point d'entrée unique du bot WhatsApp, appelé par le webhook Callbell à
 * chaque message de la cliente (même principe que le bot Rachoux Traiteur).
 */
class BotDialogueController extends Controller
{
  /**
   * POST /api/bot/v1/dialogue?bot_token=…  {phone, reponse, vocal?, piece_jointe?}
   *
   * « vocal » : transcription d'une note vocale (action Callbell « Transcrire
   * un fichier audio »), transmise à l'IA comme un message écrit.
   * « piece_jointe » : URL du dernier fichier reçu (audio, photo), pour ne
   * pas prendre cette URL pour un message.
   *
   * Répond toujours 200 : {ok, etat, text}.
   * - etat « async » : l'IA travaille ; le flux appelle ensuite le webhook
   *   « Résultat » (POST /api/bot/v1/resultat) pour récupérer la réponse ;
   * - etat « ia » : réponse directe (mode synchrone) ;
   * - etat « rx_humain » : Callbell assigne la conversation à l'équipe.
   *
   * @param Request $request Requête
   * @param BotAiAgent $agent Conseillère IA
   * @param WhatsAppNotifier $whatsapp Envoi via l'API Callbell (mode api)
   * @param BotReplyBox $box Réponses en attente
   * @return JsonResponse Réponse à afficher sur WhatsApp
   */
  public function __invoke(Request $request, BotAiAgent $agent, WhatsAppNotifier $whatsapp, BotReplyBox $box): JsonResponse
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

    // Pour un vocal ou une photo, Callbell peut mettre l'URL du fichier en guise de texte.
    if ($message !== '' && ($message === $attachment || ($attachment !== '' && preg_match('#^https?://\S+$#', $message)))) {
      $message = '';
    }

    // La transcription Callbell est rangée dans une variable partagée : si elle
    // a échoué (pas d'audio), la variable garde la dernière réponse envoyée.
    if ($voice !== '' && ($message === '' || $message === $voice) && !$agent->isLastReply($phone, $voice)) {
      $message = '[Note vocale] ' . $voice;
    }

    $replyLater = config('bot.ai.async')
      && filled(config('services.anthropic.key'))
      && (config('bot.ai.reply_channel') !== 'api' || $whatsapp->isConfigured())
      && !$agent->isWithTeam($phone);

    if ($replyLater) {
      $box->busy($phone);
      dispatch(new BotAiReplyJob($phone, $message))->afterResponse();

      return response()->json(['ok' => true, 'etat' => BotAiAgent::STATE_ASYNC, 'text' => '']);
    }

    return response()->json(['ok' => true] + $agent->handle($phone, $message));
  }

  /**
   * Callbell insère les variables telles quelles dans le corps JSON, sans
   * échapper guillemets ni retours à la ligne : un message ou une
   * transcription sur plusieurs lignes donne un JSON invalide, la requête
   * échouait (422) et Callbell renvoyait alors l'ancienne réponse. On relit
   * le corps champ par champ, dans l'ordre du modèle Callbell.
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
   * Ignore une variable Callbell vide ou non remplacée (« {{…}} »).
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
