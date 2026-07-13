<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Commande passée par un client sur la boutique.
 */
class Order extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'order_number',
    'user_id',
    'status',
    'payment_method',
    'subtotal',
    'shipping_amount',
    'discount_amount',
    'tax_amount',
    'total',
    'currency',
    'notes',
    'tracking_number',
    'shipment_notified_tracking',
    'fulfillment_type',
    'shipped_at',
    'delivered_at',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'status' => OrderStatus::class,
      'payment_method' => PaymentMethod::class,
      'subtotal' => 'decimal:2',
      'shipping_amount' => 'decimal:2',
      'discount_amount' => 'decimal:2',
      'tax_amount' => 'decimal:2',
      'total' => 'decimal:2',
      'shipped_at' => 'datetime',
      'delivered_at' => 'datetime',
    ];
  }

  /**
   * Client ayant passé la commande.
   *
   * @return BelongsTo<User, $this>
   */
  public function user(): BelongsTo
  {
    return $this->belongsTo(User::class);
  }

  /**
   * Lignes de la commande.
   *
   * @return HasMany<OrderItem, $this>
   */
  public function items(): HasMany
  {
    return $this->hasMany(OrderItem::class);
  }

  /**
   * Adresses de livraison et facturation.
   *
   * @return HasMany<OrderAddress, $this>
   */
  public function addresses(): HasMany
  {
    return $this->hasMany(OrderAddress::class);
  }

  /**
   * Adresse de livraison.
   *
   * @return HasOne<OrderAddress, $this>
   */
  public function shippingAddress(): HasOne
  {
    return $this->hasOne(OrderAddress::class)->whereIn('type', ['shipping', 'pickup']);
  }

  /**
   * Paiement associé à la commande.
   *
   * @return HasOne<Payment, $this>
   */
  public function payment(): HasOne
  {
    return $this->hasOne(Payment::class);
  }

  /**
   * Clé de route pour les URLs (numéro de commande).
   *
   * @return string Nom de la colonne
   */
  public function getRouteKeyName(): string
  {
    return 'order_number';
  }
}
