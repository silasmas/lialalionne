<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Models\BotConversation;
use App\Services\BotAi\BotAiAgent;
use App\Services\BotAi\BotReplyBox;
use App\Services\BotCommerceService;
use App\Services\WhatsAppNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Livraison des réponses différées de l'IA au flux Callbell, et diagnostic.
 */
class BotReplyController extends Controller
{
  /**
   * POST /api/bot/v1/resultat?bot_token=…  {phone}
   *
   * Attend jusqu'à BOT_AI_POLL_SECONDS (8 s par défaut, sous la limite de
   * 10 s de Callbell) que la réponse de l'IA soit prête.
   * - {etat: « ia » | « rx_humain », text} : réponse à afficher ;
   * - {etat: « async », text: ""} : toujours en préparation, rappeler ;
   * - {etat: « vide », text: ""} : rien en attente.
   *
   * @param Request $request Requête
   * @param BotReplyBox $box Réponses en attente
   * @return JsonResponse Réponse
   */
  public function result(Request $request, BotReplyBox $box): JsonResponse
  {
    $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
    $phone = $data['phone'];
    $deadline = microtime(true) + max(1.0, (float) config('bot.ai.poll_seconds', 8));

    do {
      $reply = $box->take($phone);

      if ($reply !== null) {
        return response()->json(['ok' => true] + $reply);
      }

      if (!$box->isBusy($phone)) {
        break;
      }

      usleep(400_000);
    } while (microtime(true) < $deadline);

    $reply = $box->take($phone);

    if ($reply !== null) {
      return response()->json(['ok' => true] + $reply);
    }

    return response()->json([
      'ok' => true,
      'etat' => $box->isBusy($phone) ? BotAiAgent::STATE_ASYNC : 'vide',
      'text' => '',
    ]);
  }

  /**
   * GET /api/bot/v1/diagnostic?phone=…&bot_token=…
   *
   * État de la configuration et dernières lignes du journal d'une
   * conversation (sans aucune clé ni secret).
   *
   * @param Request $request Requête
   * @param BotCommerceService $bot Normalisation du numéro
   * @param WhatsAppNotifier $whatsapp Configuration Callbell
   * @param BotReplyBox $box Réponses en attente
   * @return JsonResponse Diagnostic
   */
  public function diagnostic(Request $request, BotCommerceService $bot, WhatsAppNotifier $whatsapp, BotReplyBox $box): JsonResponse
  {
    $phone = (string) $request->query('phone', '');
    $conversation = $phone !== ''
      ? BotConversation::query()->where('phone', $bot->normalizePhone($phone))->first()
      : null;

    return response()->json([
      'config' => [
        'anthropic_key' => filled(config('services.anthropic.key')),
        'model' => config('bot.ai.model'),
        'async' => (bool) config('bot.ai.async'),
        'reply_channel' => config('bot.ai.reply_channel'),
        'callbell_api' => $whatsapp->isConfigured(),
        'callbell_team' => filled(config('services.callbell.team_uuid')),
      ],
      'conversation' => $conversation ? [
        'status' => $conversation->status,
        'handoff_reason' => $conversation->handoff_reason,
        'last_message_at' => $conversation->last_message_at?->toIso8601String(),
        'busy' => $box->isBusy($phone),
        'journal' => $conversation->messages()->latest('id')->limit(15)->get()
          ->map(fn ($message) => [
            'at' => $message->created_at?->toIso8601String(),
            'role' => $message->role,
            'content' => mb_substr((string) $message->content, 0, 300),
            'meta' => $message->role === 'tool'
              ? ['is_error' => $message->meta['is_error'] ?? null, 'result' => mb_substr((string) ($message->meta['result'] ?? ''), 0, 300)]
              : ($message->meta ?? null),
          ])->all(),
      ] : null,
    ]);
  }
}
