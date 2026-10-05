<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Conversation WhatsApp d'une cliente avec la conseillère IA.
 *
 * status : « bot » (l'IA répond) ou « human » (transférée à l'équipe).
 * history : échanges récents au format de l'API Claude (mémoire courte).
 */
class BotConversation extends Model
{
  public const STATUS_BOT = 'bot';

  public const STATUS_HUMAN = 'human';

  protected $fillable = [
    'phone',
    'user_id',
    'status',
    'history',
    'meta',
    'handoff_reason',
    'handed_off_at',
    'last_message_at',
  ];

  protected function casts(): array
  {
    return [
      'history' => 'array',
      'meta' => 'array',
      'handed_off_at' => 'datetime',
      'last_message_at' => 'datetime',
    ];
  }

  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  public function messages(): HasMany
  {
    return $this->hasMany(BotMessage::class);
  }

  /**
   * Ajoute une ligne au journal de la conversation.
   *
   * @param string $role user, assistant ou tool
   * @param string|null $content Texte
   * @param array<string, mixed> $meta Détails (outil, entrée, résultat, jetons)
   * @return BotMessage Ligne créée
   */
  public function log(string $role, ?string $content, array $meta = []): BotMessage
  {
    return $this->messages()->create([
      'role' => $role,
      'content' => $content,
      'meta' => $meta ?: null,
    ]);
  }
}
