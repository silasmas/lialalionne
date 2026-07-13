<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Page de listing des clients admin.
 */
class ListUsers extends ListRecords
{
  protected static string $resource = UserResource::class;

  /**
   * @return array<int, \Filament\Actions\Action>
   */
  protected function getHeaderActions(): array
  {
    return [];
  }
}
