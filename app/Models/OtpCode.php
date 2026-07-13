<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Code OTP temporaire pour authentification client.
 */
class OtpCode extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'identifier',
    'channel',
    'purpose',
    'code',
    'attempts',
    'expires_at',
    'verified_at',
    'metadata',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'expires_at' => 'datetime',
      'verified_at' => 'datetime',
      'metadata' => 'array',
    ];
  }

  /**
   * Indique si le code OTP est encore valide.
   *
   * @return bool True si non expiré et non vérifié
   */
  public function isValid(): bool
  {
    return $this->verified_at === null && $this->expires_at->isFuture();
  }
}
