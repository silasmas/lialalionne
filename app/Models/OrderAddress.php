<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adresse de livraison ou de facturation liée à une commande.
 */
class OrderAddress extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_id',
    'type',
    'first_name',
    'last_name',
    'phone',
    'address_line_1',
    'address_line_2',
    'city',
    'state',
    'postal_code',
    'country',
  ];

  /**
   * Commande associée.
   *
   * @return BelongsTo<Order, $this>
   */
  public function order(): BelongsTo
  {
    return $this->belongsTo(Order::class);
  }

  /**
   * Retourne le nom complet du destinataire.
   *
   * @return string Prénom et nom concaténés
   */
  public function fullName(): string
  {
    return trim("{$this->first_name} {$this->last_name}");
  }
}
