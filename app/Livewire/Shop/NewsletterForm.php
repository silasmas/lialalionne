<?php

namespace App\Livewire\Shop;

use App\Livewire\Shop\Concerns\DispatchesShopToast;
use App\Models\NewsletterSubscriber;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Formulaire d'inscription à la newsletter (footer boutique).
 */
class NewsletterForm extends Component
{
  use DispatchesShopToast;

  public string $email = '';

  public bool $subscribed = false;

  /**
   * Inscrit l'adresse e-mail à la newsletter.
   *
   * @return void
   */
  public function subscribe(): void
  {
    $this->validate([
      'email' => ['required', 'email', 'max:255'],
    ], [
      'email.required' => 'Votre adresse e-mail est obligatoire.',
      'email.email' => 'Adresse e-mail invalide.',
    ]);

    $existing = NewsletterSubscriber::query()->where('email', $this->email)->first();

    if ($existing) {
      $existing->update([
        'unsubscribed_at' => null,
        'user_id' => $existing->user_id ?? Auth::id(),
      ]);
    } else {
      NewsletterSubscriber::query()->create([
        'email' => $this->email,
        'user_id' => Auth::id(),
        'subscribed_at' => now(),
      ]);
    }

    $this->subscribed = true;
    $this->email = '';
    $this->dispatchShopToast('Merci ! Vous êtes inscrit(e) à la newsletter.', 'success');
  }

  /**
   * Rendu du formulaire newsletter.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    return view('livewire.shop.newsletter-form');
  }
}
