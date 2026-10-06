<?php

namespace App\Filament\Resources\AdminUsers\Pages;

use App\Filament\Resources\AdminUsers\AdminUserResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Édition d'un utilisateur administrateur et de ses rôles.
 */
class EditAdminUser extends EditRecord
{
  protected static string $resource = AdminUserResource::class;

  /**
   * Actions d'en-tête de la page d'édition.
   *
   * @return array<int, \Filament\Actions\Action>
   */
  protected function getHeaderActions(): array
  {
    return [
      DeleteAction::make(),
    ];
  }

  /**
   * Conserve l'accès admin tant que le compte reste dans cette ressource.
   *
   * @param array<string, mixed> $data Données du formulaire
   * @return array<string, mixed> Données mutées
   */
  protected function mutateFormDataBeforeSave(array $data): array
  {
    $data['is_admin'] = (bool) ($data['is_admin'] ?? true);

    return $data;
  }
}
