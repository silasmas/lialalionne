<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Inscrit à la newsletter Lialalionne.
 */
class NewsletterSubscriber extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'email',
    'user_id',
    'subscribed_at',
    'unsubscribed_at',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'subscribed_at' => 'datetime',
      'unsubscribed_at' => 'datetime',
    ];
  }

  /**
   * Client associé, si l'inscription provient d'un compte connecté.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
