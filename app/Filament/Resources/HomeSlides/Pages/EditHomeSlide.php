<?php

namespace App\Filament\Resources\HomeSlides\Pages;

use App\Filament\Resources\HomeSlides\HomeSlideResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d'un slide d'accueil.
 */
class EditHomeSlide extends EditRecord
{
  protected static string $resource = HomeSlideResource::class;

  /**
   * Actions d'en-tête de la fiche.
   *
   * @return array<int, DeleteAction>
   */
  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
    ];
  }
}
