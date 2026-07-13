<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use Filament\Resources\Pages\ListRecords;

/**
 * Page de listing des commandes admin.
 */
class ListOrders extends ListRecords
{
  protected static string $resource = OrderResource::class;

  /**
   * Pas de création manuelle — les commandes viennent de la boutique.
   *
   * @return array<int, \Filament\Actions\Action>
   */
  protected function getHeaderActions(): array
  {
    return [];
  }
}
