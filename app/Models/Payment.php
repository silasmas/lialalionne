<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Paiement enregistré pour une commande.
 */
class Payment extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_id',
    'method',
    'status',
    'amount',
    'currency',
    'transaction_id',
    'metadata',
    'paid_at',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'method' => PaymentMethod::class,
      'status' => PaymentStatus::class,
      'amount' => 'decimal:2',
      'metadata' => 'array',
      'paid_at' => 'datetime',
    ];
  }

  /**
   * Commande liée au paiement.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }
}
