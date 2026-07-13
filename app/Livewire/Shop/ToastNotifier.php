<?php

namespace App\Livewire\Shop;

use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Affiche les notifications toast globales (panier, favoris, checkout).
 */
class ToastNotifier extends Component
{
  public ?string $message = null;

  public string $type = 'success';

  public bool $visible = false;

  public int $toastId = 0;

  /**
   * Reçoit un événement toast depuis n'importe quel composant Livewire.
   *
   * @param string $message Texte affiché
   * @param string $type success ou error
   * @return void
   */
  #[On('shop-toast')]
  public function notify(string $message, string $type = 'success'): void
  {
    $this->message = $message;
    $this->type = $type === 'error' ? 'error' : 'success';
    $this->toastId++;
    $this->visible = true;
  }

  /**
   * Masque le toast après l'animation.
   *
   * @return void
   */
  public function hideToast(): void
  {
    $this->visible = false;
  }

  /**
   * Rendu du conteneur toast fixe.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    return view('livewire.shop.toast-notifier');
  }
}
