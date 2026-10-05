<?php

namespace Database\Seeders;

use App\Enums\SlideMediaType;
use App\Models\HomeSlide;
use Illuminate\Database\Seeder;

/**
 * Initialise les slides d'accueil à partir des bannières existantes.
 */
class HomeSlideSeeder extends Seeder
{
  /**
   * Crée les slides photo par défaut si la table est vide.
   *
   * @return void
   */
  public function run(): void
  {
    if (HomeSlide::query()->exists()) {
      return;
    }

    foreach (HomeSlide::defaultCatalog() as $slide) {
      HomeSlide::query()->create([
        'media_type' => SlideMediaType::Image,
        'image_path' => $slide['image_path'],
        'kicker' => $slide['kicker'],
        'title' => $slide['title'],
        'button_label' => $slide['button_label'],
        'button_url' => $slide['button_url'],
        'sort_order' => $slide['sort_order'],
        'is_active' => true,
      ]);
    }
  }
}
