<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Paramètre applicatif stocké en clé/valeur JSON.
 */
class Setting extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'key',
    'value',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'value' => 'array',
    ];
  }
}
