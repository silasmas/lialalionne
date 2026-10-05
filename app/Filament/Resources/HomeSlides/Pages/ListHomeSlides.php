<?php

namespace App\Filament\Resources\HomeSlides\Pages;

use App\Filament\Resources\HomeSlides\HomeSlideResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des slides d'accueil.
 */
class ListHomeSlides extends ListRecords
{
  protected static string $resource = HomeSlideResource::class;

  /**
   * Actions d'en-tête de la liste.
   *
   * @return array<int, CreateAction>
   */
  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
