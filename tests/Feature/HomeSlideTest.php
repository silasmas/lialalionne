<?php

namespace Tests\Feature;

use App\Enums\SlideMediaType;
use App\Models\HomeSlide;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests des slides d'accueil (photo, vidéo, visibilité admin).
 */
class HomeSlideTest extends TestCase
{
  use RefreshDatabase;

  /**
   * Vérifie le repli photo historique quand aucun slide n'est en base.
   *
   * @return void
   */
  public function testHomePageFallsBackToDefaultPhotoSlides(): void
  {
    $this->get(route('home'))
      ->assertOk()
      ->assertSee('La Purge Fessier')
      ->assertSee('Sublimez vos formes');
  }

  /**
   * Vérifie qu'un slide vidéo d'animation est rendu sur l'accueil.
   *
   * @return void
   */
  public function testHomePageRendersActiveVideoSlide(): void
  {
    HomeSlide::query()->create([
      'media_type' => SlideMediaType::Video,
      'video_path' => 'home-slides/animation.mp4',
      'poster_path' => 'shopwise/assets/images/banner1.jpg',
      'kicker' => 'Nouvelle campagne',
      'title' => 'Vidéo d\'animation Lialalionne',
      'button_label' => 'Découvrir',
      'button_url' => '/boutique',
      'sort_order' => 1,
      'is_active' => true,
    ]);

    $this->get(route('home'))
      ->assertOk()
      ->assertSee('banner_slide_video', false)
      ->assertSee('home-slides/animation.mp4', false)
      ->assertSee('Découvrir')
      ->assertDontSee('La Purge Fessier');
  }

  /**
   * Vérifie qu'un slide inactif n'apparaît pas.
   *
   * @return void
   */
  public function testInactiveSlideIsHiddenOnHomePage(): void
  {
    HomeSlide::query()->create([
      'media_type' => SlideMediaType::Image,
      'image_path' => 'shopwise/assets/images/banner2.jpg',
      'title' => 'Slide masqué',
      'sort_order' => 1,
      'is_active' => false,
    ]);

    HomeSlide::query()->create([
      'media_type' => SlideMediaType::Image,
      'image_path' => 'shopwise/assets/images/banner1.jpg',
      'title' => 'Slide visible',
      'sort_order' => 2,
      'is_active' => true,
    ]);

    $this->get(route('home'))
      ->assertOk()
      ->assertSee('Slide visible')
      ->assertDontSee('Slide masqué');
  }

  /**
   * Vérifie la normalisation du lien bouton.
   *
   * @return void
   */
  public function testButtonHrefNormalizesRelativeUrls(): void
  {
    $slide = HomeSlide::make([
      'button_label' => 'Voir',
      'button_url' => 'boutique',
    ]);

    $this->assertSame('/boutique', $slide->buttonHref());
  }
}
