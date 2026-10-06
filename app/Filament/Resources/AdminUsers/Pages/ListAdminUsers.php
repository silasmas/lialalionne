<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Liste des utilisateurs administrateurs.
 */
class ListAdminUsers extends ListRecords
{
  protected static string $resource = AdminUserResource::class;

  /**
   * Actions d'en-tête de la liste.
   *
   * @return array<int, \Filament\Actions\Action>
   */
  protected function getHeaderActions(): array
  {
    return [
      CreateAction::make(),
    ];
  }
}
