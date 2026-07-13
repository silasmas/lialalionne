<?php

namespace App\Livewire\Shop;

use Livewire\Component;

/**
 * Affiche une page légale statique (CGV, confidentialité, retours).
 */
class LegalPage extends Component
{
  public string $page;

  /**
   * Pages légales disponibles avec titre et vue associée.
   *
   * @var array<string, array{title: string, view: string}>
   */
  private const PAGES = [
    'cgv' => [
      'title' => 'Conditions générales de vente',
      'view' => 'livewire.shop.legal.cgv',
    ],
    'confidentialite' => [
      'title' => 'Politique de confidentialité',
      'view' => 'livewire.shop.legal.confidentialite',
    ],
    'retours' => [
      'title' => 'Retours & remboursements',
      'view' => 'livewire.shop.legal.retours',
    ],
  ];

  /**
   * Valide la page demandée.
   *
   * @param string $page Identifiant de la page légale
   * @return void
   */
  public function mount(string $page): void
  {
    if (!array_key_exists($page, self::PAGES)) {
      abort(404);
    }

    $this->page = $page;
  }

  /**
   * Rendu de la page légale.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    $config = self::PAGES[$this->page];

    return view('livewire.shop.legal-page', [
      'pageTitle' => $config['title'],
      'contentView' => $config['view'],
    ])->layout('layouts.shop', [
      'title' => $config['title'] . ' — Lialalionne',
      'metaDescription' => $config['title'] . ' — Lialalionne, soins corporels premium.',
    ]);
  }
}
