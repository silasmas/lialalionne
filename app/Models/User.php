<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable([
  'name',
  'email',
  'phone',
  'password',
  'is_admin',
  'delivery_address_line_1',
  'delivery_address_line_2',
  'delivery_city',
  'delivery_postal_code',
  'delivery_country',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements FilamentUser, HasName
{
  /** @use HasFactory<UserFactory> */
  use HasFactory, Notifiable;

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'email_verified_at' => 'datetime',
      'password' => 'hashed',
      'is_admin' => 'boolean',
    ];
  }

  /**
   * Détermine si l'utilisateur peut accéder au panel Filament.
   *
   * @param Panel $panel Panel admin Filament
   * @return bool True si accès autorisé
   */
  public function canAccessPanel(Panel $panel): bool
  {
    return $this->is_admin;
  }

  /**
   * Nom affiché dans Filament (menu utilisateur, widgets compte).
   *
   * @return string Libellé non vide pour l'interface admin
   */
  public function getFilamentName(): string
  {
    $name = trim((string) ($this->name ?? ''));

    if ($name !== '') {
      return $name;
    }

    if ($this->email) {
      return (string) $this->email;
    }

    if ($this->phone) {
      return (string) $this->phone;
    }

    return 'Utilisateur';
  }

  /**
   * Commandes passées par le client.
   *
   * @return HasMany<Order, $this>
   */
  public function orders(): HasMany
  {
    return $this->hasMany(Order::class);
  }

  /**
   * Panier actif du client.
   *
   * @return HasOne<Cart, $this>
   */
  public function cart(): HasOne
  {
    return $this->hasOne(Cart::class);
  }

  /**
   * Produits mis en favori par le client.
   *
   * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany<Product, $this>
   */
  public function favoriteProducts(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
  {
    return $this->belongsToMany(Product::class, 'product_favorites')
      ->withTimestamps();
  }
}
