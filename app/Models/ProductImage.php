<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * Image associée à un produit.
 */
class ProductImage extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'product_id',
    'path',
    'alt_text',
    'sort_order',
    'is_primary',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'sort_order' => 'integer',
      'is_primary' => 'boolean',
    ];
  }

  /**
   * Produit lié à l'image.
   *
   * @return BelongsTo<Product, $this>
   */
  public function product(): BelongsTo
  {
    return $this->belongsTo(Product::class);
  }

  /**
   * URL publique de l'image (compatible Filament FileUpload).
   *
   * @return Attribute<string|null, never>
   */
  protected function url(): Attribute
  {
    return Attribute::get(function (): ?string {
      $path = $this->normalizedPath();

      if ($path === null) {
        return null;
      }

      if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
      }

      return Storage::disk('public')->url($path);
    });
  }

  /**
   * Normalise le chemin stocké (string, JSON array Filament, préfixes inutiles).
   *
   * @return string|null Chemin relatif disque public
   */
  public function normalizedPath(): ?string
  {
    $path = $this->path;

    if (is_array($path)) {
      $path = $path[0] ?? null;
    }

    if (!is_string($path) || trim($path) === '') {
      return null;
    }

    $path = trim($path);

    if (str_starts_with($path, '[')) {
      $decoded = json_decode($path, true);
      $path = is_array($decoded) ? ($decoded[0] ?? null) : $path;
    }

    if (!is_string($path) || trim($path) === '') {
      return null;
    }

    $path = ltrim($path, '/');
    $path = preg_replace('#^(public/|storage/)#', '', $path) ?? $path;

    return $path !== '' ? $path : null;
  }
}
