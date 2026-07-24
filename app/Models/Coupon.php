<?php

namespace App\Models;

use App\Enums\CouponType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Code promotionnel applicable au checkout.
 */
class Coupon extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'code',
    'name',
    'type',
    'value',
    'min_order_amount',
    'max_discount_amount',
    'max_uses',
    'max_uses_per_user',
    'times_used',
    'starts_at',
    'ends_at',
    'is_active',
    'description',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'type' => CouponType::class,
      'value' => 'decimal:2',
      'min_order_amount' => 'decimal:2',
      'max_discount_amount' => 'decimal:2',
      'max_uses' => 'integer',
      'max_uses_per_user' => 'integer',
      'times_used' => 'integer',
      'starts_at' => 'datetime',
      'ends_at' => 'datetime',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Commandes ayant utilisé ce code.
   *
   * @return HasMany<Order, $this>
   */
  public function orders(): HasMany
  {
    return $this->hasMany(Order::class);
  }

  /**
   * Normalise un code saisi (majuscules, sans espaces).
   *
   * @param string $code Code brut
   * @return string Code normalisé
   */
  public static function normalizeCode(string $code): string
  {
    return strtoupper(trim($code));
  }

  /**
   * Stocke toujours le code en majuscules.
   *
   * @return Attribute<string, string>
   */
  protected function code(): Attribute
  {
    return Attribute::make(
      set: fn (string $value): string => self::normalizeCode($value),
    );
  }

  /**
   * Indique si le code est actuellement utilisable (actif + dates).
   *
   * @return bool True si valide dans le temps
   */
  public function isCurrentlyValid(): bool
  {
    if (!$this->is_active) {
      return false;
    }

    $now = now();

    if ($this->starts_at && $now->lt($this->starts_at)) {
      return false;
    }

    if ($this->ends_at && $now->gt($this->ends_at)) {
      return false;
    }

    return true;
  }

  /**
   * Indique si le quota global d'utilisations est atteint.
   *
   * @return bool True si plus d'utilisations possibles
   */
  public function hasReachedGlobalLimit(): bool
  {
    return $this->max_uses !== null && $this->times_used >= $this->max_uses;
  }
}
