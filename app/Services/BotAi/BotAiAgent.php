<?php

namespace App\Services\BotAi;

use App\Models\BotConversation;
use App\Services\BotCommerceService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Conseillère IA Chez Lia : reçoit chaque message WhatsApp (relayé par
 * Callbell), le fait comprendre par Claude avec les consignes de la boutique
 * et des outils branchés sur le site, puis renvoie le texte à afficher.
 *
 * Réponse au même format que le bot Rachoux : {etat, text}. L'état
 * « rx_humain » signale à Callbell d'assigner la conversation à l'équipe.
 */
class BotAiAgent
{
  public const STATE_AI = 'ia';

  public const STATE_HUMAN = 'rx_humain';

  /**
   * @param BotAiTools $tools Outils de l'IA
   * @param BotCommerceService $bot Catalogue et clientes
   */
  public function __construct(
    private readonly BotAiTools $tools,
    private readonly BotCommerceService $bot
  ) {
  }

  /**
   * Traite un message de la cliente.
   *
   * @param string $phone Numéro WhatsApp
   * @param string $message Texte reçu (peut être vide : image, vocal…)
   * @return array{etat: string, text: string} Réponse pour Callbell
   */
  public function handle(string $phone, string $message): array
  {
    $normalized = $this->bot->normalizePhone($phone);
    $conversation = BotConversation::query()->firstOrCreate(['phone' => $normalized]);
    $this->resetIfStale($conversation);

    $message = trim($message);
    $userText = $message !== '' ? mb_substr($message, 0, 2000) : '[message sans texte : photo, vocal ou autre contenu]';

    $conversation->log('user', $userText);
    $conversation->last_message_at = now();
    $conversation->save();

    if ($conversation->status === BotConversation::STATUS_HUMAN) {
      return $this->reply($conversation, self::STATE_HUMAN, 'Une conseillère a bien votre message et vous répond très vite 💛');
    }

    if (!filled(config('services.anthropic.key'))) {
      return $this->handoff($conversation, 'IA non configurée (ANTHROPIC_API_KEY manquante)');
    }

    $history = $conversation->history ?? [];
    $history[] = ['role' => 'user', 'content' => $userText];

    try {
      [$history, $text] = $this->converse($conversation, $history);
    } catch (\Throwable $exception) {
      Log::error('Bot IA : échec de l\'appel à Claude', ['phone' => $normalized, 'error' => $exception->getMessage()]);

      return $this->handoff($conversation, 'Erreur technique IA : ' . mb_substr($exception->getMessage(), 0, 150));
    }

    $conversation->refresh();
    $conversation->history = $this->trim($history);
    $conversation->save();

    $state = $conversation->status === BotConversation::STATUS_HUMAN ? self::STATE_HUMAN : self::STATE_AI;

    if ($text === '') {
      $text = $state === self::STATE_HUMAN
        ? 'Je transmets à une conseillère, elle vous répond très vite 💛'
        : 'Pouvez-vous reformuler votre demande ? 🙂';
    }

    return $this->reply($conversation, $state, $text);
  }

  /**
   * Boucle message → outils → message jusqu'à la réponse finale.
   *
   * @param BotConversation $conversation Conversation
   * @param list<array<string, mixed>> $history Historique au format API
   * @return array{0: list<array<string, mixed>>, 1: string} Historique et texte final
   */
  private function converse(BotConversation $conversation, array $history): array
  {
    $rounds = max(1, (int) config('bot.ai.max_tool_rounds', 6));
    $system = $this->systemPrompt($conversation);

    for ($round = 0; $round <= $rounds; $round++) {
      $response = $this->callClaude($system, $history, $round < $rounds);
      $content = $response['content'] ?? [];
      $history[] = ['role' => 'assistant', 'content' => $content];

      $toolUses = array_values(array_filter($content, fn ($block) => ($block['type'] ?? null) === 'tool_use'));

      if (($response['stop_reason'] ?? null) !== 'tool_use' || $toolUses === []) {
        $text = collect($content)
          ->where('type', 'text')
          ->pluck('text')
          ->implode("\n");

        return [$history, trim($text)];
      }

      $results = [];

      foreach ($toolUses as $toolUse) {
        $outcome = $this->tools->execute((string) $toolUse['name'], (array) ($toolUse['input'] ?? []), $conversation);

        $conversation->log('tool', $toolUse['name'], [
          'input' => $toolUse['input'] ?? [],
          'is_error' => $outcome['is_error'],
          'result' => mb_substr((string) json_encode($outcome['result'], JSON_UNESCAPED_UNICODE), 0, 2000),
        ]);

        $results[] = [
          'type' => 'tool_result',
          'tool_use_id' => $toolUse['id'],
          'content' => (string) json_encode($outcome['result'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
          'is_error' => $outcome['is_error'],
        ];
      }

      $history[] = ['role' => 'user', 'content' => $results];
    }

    return [$history, ''];
  }

  /**
   * Appel à l'API Messages de Claude.
   *
   * @param list<array<string, mixed>> $system Blocs du prompt système
   * @param list<array<string, mixed>> $history Messages
   * @param bool $withTools False au dernier tour pour forcer une réponse texte
   * @return array<string, mixed> Réponse décodée
   */
  private function callClaude(array $system, array $history, bool $withTools): array
  {
    $payload = [
      'model' => config('bot.ai.model'),
      'max_tokens' => (int) config('bot.ai.max_tokens', 1024),
      'system' => $system,
      'messages' => $this->sanitize($history),
      'tools' => $this->tools->definitions(),
    ];

    if (!$withTools) {
      $payload['tool_choice'] = ['type' => 'none'];
    }

    $response = Http::withHeaders([
      'x-api-key' => (string) config('services.anthropic.key'),
      'anthropic-version' => '2023-06-01',
    ])
      ->connectTimeout(5)
      ->timeout(45)
      ->acceptJson()
      ->post((string) config('services.anthropic.url'), $payload);

    if (!$response->successful()) {
      throw new \RuntimeException('API Claude ' . $response->status() . ' : ' . mb_substr($response->body(), 0, 300));
    }

    return (array) $response->json();
  }

  /**
   * Un outil sans paramètre doit être envoyé avec un objet JSON vide ({}),
   * pas un tableau ([]) — ce que redonne la base après décodage.
   *
   * @param list<array<string, mixed>> $history Historique
   * @return list<array<string, mixed>> Historique prêt pour l'API
   */
  private function sanitize(array $history): array
  {
    return array_map(function (array $message) {
      if (!is_array($message['content'])) {
        return $message;
      }

      $message['content'] = array_map(function ($block) {
        if (is_array($block) && ($block['type'] ?? null) === 'tool_use' && empty($block['input'])) {
          $block['input'] = new \stdClass();
        }

        return $block;
      }, $message['content']);

      return $message;
    }, $history);
  }

  /**
   * Consignes (fichier éditable) + contexte boutique (mis en cache côté API)
   * + profil de la cliente (variable).
   *
   * @param BotConversation $conversation Conversation
   * @return list<array<string, mixed>> Blocs système
   */
  private function systemPrompt(BotConversation $conversation): array
  {
    $path = (string) config('bot.ai.prompt_path');
    $instructions = is_file($path) ? (string) file_get_contents($path) : '';

    $shop = $this->bot->shopInfo();
    $catalogue = $this->bot->catalogue()
      ->map(fn (array $p) => sprintf(
        '- %s | %s | %s | %s | %s%s',
        $p['sku'],
        $p['name'],
        $p['category'] ?? '-',
        $p['price']['label'],
        $p['in_stock'] ? 'en stock' : 'RUPTURE',
        $p['variants'] ? ' | variantes : ' . collect($p['variants'])->map(fn ($v) => $v['name'] . ' ' . $v['price']['label'])->implode(', ') : ''
      ) . "\n  " . ($p['short_description'] ?? ''))
      ->implode("\n");

    $routines = $this->bot->routines()
      ->map(fn (array $r) => '- ' . collect($r['products'])->pluck('name')->implode(' + ')
        . ' : ' . $r['price_separately']['label'] . ' séparément, kit ' . $r['kit_price']['label'])
      ->implode("\n");

    $shipping = collect($shop['shipping_rates'])
      ->map(fn ($rate) => "- {$rate['name']} (id {$rate['id']}) : {$rate['price']['label']}")
      ->implode("\n");

    $payments = collect($shop['payment_methods'])->pluck('label')->implode(', ');
    $pickup = $shop['pickup']['enabled']
      ? ($shop['pickup']['store_name'] ?? 'Boutique') . ' — ' . ($shop['pickup']['address'] ?? '')
      : 'non disponible';

    $context = <<<TXT
# Contexte boutique (données réelles du site, à jour)

Catalogue (SKU | nom | catégorie | prix | stock) :
{$catalogue}

Routines :
{$routines}

Livraison :
{$shipping}
Retrait en boutique : {$pickup}
Moyens de paiement disponibles : {$payments}
Devise principale : {$shop['primary_currency']}
Site : {$this->siteUrl()}
TXT;

    $customer = $this->bot->findCustomer($conversation->phone);
    $profile = $customer
      ? 'Cliente connue : ' . $customer->name . ', ' . $customer->orders()->count() . ' commande(s), '
        . (int) $customer->loyalty_points_balance . ' points fidélité.'
      : 'Nouvelle cliente (pas encore de compte) : demande son nom au moment de la commande.';

    return [
      ['type' => 'text', 'text' => $instructions],
      ['type' => 'text', 'text' => $context, 'cache_control' => ['type' => 'ephemeral']],
      ['type' => 'text', 'text' => "# Cette conversation\n\nNuméro WhatsApp : +{$conversation->phone}\n{$profile}\nDate et heure à Kinshasa : " . now('Africa/Kinshasa')->locale('fr')->isoFormat('dddd D MMMM YYYY, HH:mm') . '.'],
    ];
  }

  /**
   * Repart de zéro après une longue pause (le journal reste intact).
   *
   * @param BotConversation $conversation Conversation
   * @return void
   */
  private function resetIfStale(BotConversation $conversation): void
  {
    $ttl = max(1, (int) config('bot.ai.session_ttl_hours', 12));

    if ($conversation->last_message_at && $conversation->last_message_at->lt(now()->subHours($ttl))) {
      $conversation->forceFill([
        'history' => [],
        'meta' => [],
        'status' => BotConversation::STATUS_BOT,
        'handoff_reason' => null,
        'handed_off_at' => null,
      ])->save();
    }
  }

  /**
   * Limite la mémoire courte en coupant toujours avant un vrai message de
   * la cliente (jamais au milieu d'un échange outil / résultat).
   *
   * @param list<array<string, mixed>> $history Historique
   * @return list<array<string, mixed>> Historique réduit
   */
  private function trim(array $history): array
  {
    $limit = max(6, (int) config('bot.ai.history_limit', 40));

    while (count($history) > $limit) {
      array_shift($history);

      while ($history !== [] && !($history[0]['role'] === 'user' && is_string($history[0]['content']))) {
        array_shift($history);
      }
    }

    return array_values($history);
  }

  /**
   * Transfère à l'équipe et prévient la cliente.
   *
   * @param BotConversation $conversation Conversation
   * @param string $reason Raison (visible dans le journal)
   * @return array{etat: string, text: string} Réponse
   */
  private function handoff(BotConversation $conversation, string $reason): array
  {
    $conversation->forceFill([
      'status' => BotConversation::STATUS_HUMAN,
      'handoff_reason' => mb_substr($reason, 0, 250),
      'handed_off_at' => now(),
    ])->save();

    return $this->reply(
      $conversation,
      self::STATE_HUMAN,
      'Bonjour et merci pour votre message 💛 Une conseillère Chez Lia vous répond très vite.'
    );
  }

  /**
   * @param BotConversation $conversation Conversation
   * @param string $state État renvoyé à Callbell
   * @param string $text Texte à afficher
   * @return array{etat: string, text: string} Réponse
   */
  private function reply(BotConversation $conversation, string $state, string $text): array
  {
    $conversation->log('assistant', $text, ['etat' => $state]);

    return ['etat' => $state, 'text' => $text];
  }

  /**
   * @return string URL publique du site
   */
  private function siteUrl(): string
  {
    return rtrim((string) config('app.url'), '/');
  }
}
