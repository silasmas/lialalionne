<?php

namespace App\Livewire\Shop;

use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CurrencyService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Icône panier dans le header avec badge et mini-panier déroulant.
 */
class CartIcon extends Component
{
  public int $count = 0;

  public string $theme = 'default';

  /**
   * Charge le nombre d'articles au montage.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  public function mount(CartService $cartService): void
  {
    $this->count = $cartService->getItemCount();
  }

  /**
   * Rafraîchit le compteur après mise à jour du panier.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  #[On('cart-updated')]
  public function refreshCount(CartService $cartService): void
  {
    $this->count = $cartService->getItemCount();
  }

  /**
   * Supprime une ligne depuis le mini-panier.
   *
   * @param int $itemId Identifiant ligne panier
   * @param CartService $cartService Service panier
   * @return void
   */
  public function removeItem(int $itemId, CartService $cartService): void
  {
    $cart = $cartService->getCartWithItems();
    $item = $cart->items->firstWhere('id', $itemId);

    if (!$item instanceof CartItem) {
      return;
    }

    try {
      $cartService->removeItem($cart, $item);
      $this->count = $cartService->getItemCount();
      $this->dispatch('cart-updated');
    } catch (ValidationException) {
      // Ignorer si la ligne n'existe plus
    }
  }

  /**
   * Rendu de l'icône panier avec contenu du mini-panier.
   *
   * @param CartService $cartService Service panier
   * @param CurrencyService $currencyService Service devises
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CartService $cartService, CurrencyService $currencyService)
  {
    $cart = $cartService->getCartWithItems();
    $view = $this->theme === 'shopwise'
      ? 'livewire.shop.cart-icon-shopwise'
      : 'livewire.shop.cart-icon';

    return view($view, [
      'items' => $cart->items,
      'subtotalFormatted' => $currencyService->formatFromEur($cart->subtotal()),
    ]);
  }
}
