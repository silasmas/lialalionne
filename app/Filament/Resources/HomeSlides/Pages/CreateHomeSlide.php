<?php

namespace App\Filament\Resources\HomeSlides\Pages;

use App\Filament\Resources\HomeSlides\HomeSlideResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'un slide d'accueil.
 */
class CreateHomeSlide extends CreateRecord
{
  protected static string $resource = HomeSlideResource::class;
}
