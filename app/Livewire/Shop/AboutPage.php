<?php

namespace App\Livewire\Shop;

use Livewire\Component;

/**
 * Page « À propos » au format template Shopwise.
 */
class AboutPage extends Component
{
  /**
   * Rendu de la page institutionnelle Lialalionne.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    return view('livewire.shop.about-page')->layout('layouts.shopwise', [
      'title' => 'À propos — Lialalionne',
      'metaDescription' => 'Découvrez Lialalionne, votre boutique de soins corporels premium à Kinshasa.',
    ]);
  }
}
