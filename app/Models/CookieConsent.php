<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Enregistrement du consentement cookies d'un visiteur (preuve RGPD).
 */
class CookieConsent extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'user_id',
    'session_id',
    'ip_address',
    'user_agent',
    'analytics',
    'consented_at',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'analytics' => 'boolean',
      'consented_at' => 'datetime',
    ];
  }

  /**
   * Client authentifié au moment du consentement, le cas échéant.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }
}
