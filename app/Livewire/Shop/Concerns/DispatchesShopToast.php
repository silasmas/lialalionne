<?php

namespace App\Livewire\Shop\Concerns;

/**
 * Envoie des notifications toast (succès / erreur) vers le layout Shopwise.
 */
trait DispatchesShopToast
{
  /**
   * Affiche une notification toast côté client.
   *
   * @param string $message Texte affiché à l'utilisateur
   * @param string $type Type visuel : success ou error
   * @return void
   */
  protected function dispatchShopToast(string $message, string $type = 'success'): void
  {
    $this->dispatch('shop-toast', message: $message, type: $type);
  }
}
