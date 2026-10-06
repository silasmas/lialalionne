<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Création d'un utilisateur administrateur.
 */
class CreateAdminUser extends CreateRecord
{
  protected static string $resource = AdminUserResource::class;

  /**
   * Force le flag admin avant création.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return array<string, mixed> Données mutées
   */
  protected function mutateFormDataBeforeCreate(array $data): array
  {
    $data['is_admin'] = true;

    return $data;
  }
}
