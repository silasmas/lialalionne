<?php

namespace App\Jobs;

use App\Services\BotAi\BotAiAgent;
use App\Services\BotAi\BotReplyBox;
use App\Services\WhatsAppNotifier;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Réponse de la conseillère IA calculée après coup.
 *
 * Callbell coupe son webhook au bout de 10 secondes : une réponse qui
 * demande plusieurs outils (devis, commande, paiement) dépasse souvent ce
 * délai. Le point d'entrée répond donc tout de suite (« async ») et ce job,
 * lancé après l'envoi de la réponse HTTP (afterResponse, aucun worker
 * requis), fait travailler l'IA puis dépose la réponse dans la boîte
 * (BotReplyBox), où le webhook « Résultat » du flux Callbell vient la
 * chercher. Avec BOT_AI_REPLY_CHANNEL=api, elle part plutôt par l'API
 * Callbell.
 */
class BotAiReplyJob
{
  use Queueable;

  /**
   * @param string $phone Numéro WhatsApp de la cliente
   * @param string $message Message reçu
   */
  public function __construct(
    public string $phone,
    public string $message
  ) {
  }

  /**
   * Traite le message (un seul à la fois par cliente) et livre la réponse.
   *
   * @param BotAiAgent $agent Conseillère IA
   * @param BotReplyBox $box Boîte des réponses en attente
   * @param WhatsAppNotifier $whatsapp Envoi Callbell (mode api)
   * @return void
   */
  public function handle(BotAiAgent $agent, BotReplyBox $box, WhatsAppNotifier $whatsapp): void
  {
    @set_time_limit(180);
    @ignore_user_abort(true);

    $lock = Cache::lock('bot-ai:' . preg_replace('/\D+/', '', $this->phone), 150);

    try {
      $lock->block(120);
    } catch (LockTimeoutException) {
      Log::warning('Bot IA : message traité sans verrou (traitement précédent trop long)', ['phone' => $this->phone]);
    }

    try {
      $result = $agent->handle($this->phone, $this->message);

      if (config('bot.ai.reply_channel') === 'api' && $whatsapp->isConfigured()) {
        $sent = $whatsapp->sendText(
          $this->phone,
          $result['text'],
          $result['etat'] === BotAiAgent::STATE_HUMAN ? $this->handoffOptions() : []
        );

        if ($sent) {
          return;
        }

        Log::error('Bot IA : réponse refusée par l\'API Callbell, déposée pour le webhook Résultat', ['phone' => $this->phone]);
      }

      $box->put($this->phone, $result['etat'], $result['text']);
    } catch (\Throwable $exception) {
      Log::error('Bot IA : échec du traitement différé', ['phone' => $this->phone, 'error' => $exception->getMessage()]);
      $box->put($this->phone, BotAiAgent::STATE_HUMAN, 'Bonjour et merci pour votre message 💛 Une conseillère Chez Lia vous répond très vite.');
    } finally {
      $box->done($this->phone);
      $lock->release();
    }
  }

  /**
   * Transfert : la conversation est assignée à l'équipe et le robot Callbell
   * s'arrête pour ce contact (comme le nœud « Assigner » du flux).
   *
   * @return array<string, string> Options Callbell
   */
  private function handoffOptions(): array
  {
    return array_filter([
      'team_uuid' => (string) config('services.callbell.team_uuid'),
      'bot_status' => 'bot_end',
    ]);
  }
}
