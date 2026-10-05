<?php

namespace App\Models;

use App\Enums\SlideMediaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Slide du carrousel d'accueil (photo ou vidéo d'animation).
 */
class HomeSlide extends Model
{
  /**
   * Attributs assignables en masse.
   *
   * @var list<string>
   */
  protected $fillable = [
    'media_type',
    'image_path',
    'video_path',
    'poster_path',
    'kicker',
    'title',
    'button_label',
    'button_url',
    'sort_order',
    'is_active',
  ];

  /**
   * Attributs castés automatiquement.
   *
   * @return array<string, string>
   */
  protected function casts(): array
  {
    return [
      'media_type' => SlideMediaType::class,
      'sort_order' => 'integer',
      'is_active' => 'boolean',
    ];
  }

  /**
   * Restreint la requête aux slides publiés, dans l'ordre d'affichage.
   *
   * @param Builder<static> $query Requête Eloquent
   * @return Builder<static> Requête filtrée
   */
  public function scopePublished(Builder $query): Builder
  {
    return $query->where('is_active', true)->orderBy('sort_order')->orderBy('id');
  }

  /**
   * Catalogue des slides photo historiques (repli accueil + seeder).
   *
   * @return list<array{
   *   image_path: string,
   *   kicker: string,
   *   title: string,
   *   button_label: string,
   *   button_url: string,
   *   sort_order: int
   * }>
   */
  public static function defaultCatalog(): array
  {
    return [
      [
        'image_path' => 'shopwise/assets/images/banner1.jpg',
        'kicker' => 'Cure détox 100% naturelle',
        'title' => 'La Purge Fessier',
        'button_label' => 'Je découvre',
        'button_url' => '/produits/la-purge-fessier',
        'sort_order' => 1,
      ],
      [
        'image_path' => 'shopwise/assets/images/banner2.jpg',
        'kicker' => 'L\'élégance Chez Lia',
        'title' => 'Sublimez vos formes',
        'button_label' => 'Voir la gamme fessier',
        'button_url' => '/boutique',
        'sort_order' => 2,
      ],
      [
        'image_path' => 'shopwise/assets/images/banner3.jpg',
        'kicker' => 'Cure minceur 14 jours',
        'title' => 'Ventre plat, sans effort',
        'button_label' => 'Je commande',
        'button_url' => '/produits/14-day-flat-tummy-tea',
        'sort_order' => 3,
      ],
      [
        'image_path' => 'shopwise/assets/images/banner4.jpg',
        'kicker' => 'Livraison Kinshasa & RDC',
        'title' => 'Votre silhouette, votre fierté',
        'button_label' => 'Explorer la boutique',
        'button_url' => '/boutique',
        'sort_order' => 4,
      ],
    ];
  }

  /**
   * Indique si le slide affiche une photo.
   *
   * @return bool True si le média est une image
   */
  public function isImage(): bool
  {
    return $this->media_type === SlideMediaType::Image;
  }

  /**
   * Indique si le slide affiche une vidéo d'animation.
   *
   * @return bool True si le média est une vidéo
   */
  public function isVideo(): bool
  {
    return $this->media_type === SlideMediaType::Video;
  }

  /**
   * Lien du bouton, normalisé en URL interne ou externe.
   *
   * @return string|null URL cliquable ou null si aucun bouton
   */
  public function buttonHref(): ?string
  {
    $url = trim((string) $this->button_url);
    $label = trim((string) $this->button_label);

    if ($url === '' || $label === '') {
      return null;
    }

    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
      return $url;
    }

    return '/' . ltrim($url, '/');
  }

  /**
   * URL publique du média principal (photo ou vidéo).
   *
   * @return string|null URL ou null si le fichier est absent
   */
  public function mediaUrl(): ?string
  {
    if ($this->isVideo()) {
      return $this->publicUrl($this->video_path);
    }

    return $this->publicUrl($this->image_path);
  }

  /**
   * URL de l'image d'attente affichée avant / sans lecture vidéo.
   *
   * @return string|null URL du poster ou de la photo de repli
   */
  public function posterUrl(): ?string
  {
    return $this->publicUrl($this->poster_path) ?? $this->publicUrl($this->image_path);
  }

  /**
   * Type MIME de la vidéo d'animation, déduit de l'extension.
   *
   * @return string Type MIME HTML5
   */
  public function videoMimeType(): string
  {
    $path = $this->normalizeStoredPath($this->video_path);
    $extension = strtolower(pathinfo((string) $path, PATHINFO_EXTENSION));

    return match ($extension) {
      'webm' => 'video/webm',
      'ogg', 'ogv' => 'video/ogg',
      default => 'video/mp4',
    };
  }

  /**
   * Construit l'URL publique d'un chemin stocké (upload ou asset Shopwise).
   *
   * @param string|null $path Chemin relatif ou URL
   * @return string|null URL publique
   */
  private function publicUrl(?string $path): ?string
  {
    $normalized = $this->normalizeStoredPath($path);

    if ($normalized === null) {
      return null;
    }

    if (str_starts_with($normalized, 'http://') || str_starts_with($normalized, 'https://')) {
      return $normalized;
    }

    if (str_starts_with($normalized, 'shopwise/')) {
      return asset($normalized);
    }

    return Storage::disk('public')->url($normalized);
  }

  /**
   * Normalise un chemin Filament (string, JSON array, préfixes inutiles).
   *
   * @param mixed $path Valeur brute en base
   * @return string|null Chemin relatif
   */
  private function normalizeStoredPath(mixed $path): ?string
  {
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
