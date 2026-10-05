<?php

namespace Database\Seeders;

use App\Enums\SlideMediaType;
use App\Models\HomeSlide;
use Illuminate\Database\Seeder;

/**
 * Active le slide vidéo « Chez Lia » et masque les bannières photo.
 */
class ChezLiaCampaignSlideSeeder extends Seeder
{
  /**
   * Remplace les slides photo par la campagne vidéo enregistrée.
   *
   * @return void
   */
  public function run(): void
  {
    HomeSlide::query()->update(['is_active' => false]);

    HomeSlide::query()->updateOrCreate(
      ['video_path' => 'home-slides/chez-lia-campagne.mp4'],
      [
        'media_type' => SlideMediaType::Video,
        'image_path' => null,
        'poster_path' => 'home-slides/chez-lia-campagne-poster.jpg',
        'kicker' => null,
        'title' => 'Chez Lia — Des soins pensés pour chaque silhouette',
        'button_label' => 'Je découvre',
        'button_url' => '/boutique',
        'sort_order' => 1,
        'is_active' => true,
      ]
    );
  }
}
