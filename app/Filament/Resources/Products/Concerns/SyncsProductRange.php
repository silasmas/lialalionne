<?php

namespace App\Filament\Resources\Products\Concerns;

/**
 * Synchronise les liens de gamme dans les deux sens après sauvegarde admin.
 */
trait SyncsProductRange
{
  /**
   * Recopie les produits liés pour que la gamme soit visible depuis chaque fiche.
   *
   * @return void
   */
  protected function afterSave(): void
  {
    $this->syncSavedRange();
  }

  /**
   * Applique la même synchro après création.
   *
   * @return void
   */
  protected function afterCreate(): void
  {
    $this->syncSavedRange();
  }

  /**
   * Relie le produit courant à sa gamme de façon bidirectionnelle.
   *
   * @return void
   */
  private function syncSavedRange(): void
  {
    $relatedIds = $this->record
      ->relatedProducts()
      ->pluck('products.id')
      ->all();

    $this->record->syncRangeCompanions($relatedIds);
  }
}
