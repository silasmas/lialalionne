<?php

namespace App\Enums;

/**
 * Type de média d'un slide d'accueil (photo ou vidéo d'animation).
 */
enum SlideMediaType: string
{
  case Image = 'image';
  case Video = 'video';

  /**
   * Retourne le libellé affichable du type de média.
   *
   * @return string Libellé en français
   */
  public function label(): string
  {
    return match ($this) {
      self::Image => 'Photo',
      self::Video => 'Vidéo d\'animation',
    };
  }
}
