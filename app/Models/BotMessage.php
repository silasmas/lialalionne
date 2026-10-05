<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Ligne du journal d'une conversation WhatsApp (message cliente, réponse
 * de l'IA ou appel d'outil).
 */
class BotMessage extends Model
{
  protected $fillable = [
    'bot_conversation_id',
    'role',
    'content',
    'meta',
  ];

  protected function casts(): array
  {
    return [
      'meta' => 'array',
    ];
  }

  public function conversation(): BelongsTo
  {
    return $this->belongsTo(BotConversation::class, 'bot_conversation_id');
  }
}
