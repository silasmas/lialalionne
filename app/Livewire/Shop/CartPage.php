<?php

namespace App\Livewire\Shop;

use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CurrencyService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Page panier : liste des articles, quantités et sous-total.
 */
class CartPage extends Component
{
  public Cart $cart;

  /**
   * Charge le panier courant avec ses articles.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  public function mount(CartService $cartService): void
  {
    $this->cart = $cartService->getCartWithItems();
  }

  /**
   * Met à jour la quantité d'un article.
   *
   * @param int $itemId Identifiant de la ligne panier
   * @param int $quantity Nouvelle quantité
   * @param CartService $cartService Service panier
   * @return void
   */
  public function updateQuantity(int $itemId, int $quantity, CartService $cartService): void
  {
    $item = $this->findCartItem($itemId);

    try {
      $cartService->updateQuantity($this->cart, $item, $quantity);
      $this->cart = $cartService->getCartWithItems();
      $this->dispatch('cart-updated');
    } catch (ValidationException $exception) {
      $errors = $exception->errors();
      $message = $errors['quantity'][0] ?? $errors['cart'][0] ?? 'Impossible de mettre à jour le panier.';
      $this->addError('cart', $message);
    }
  }

  /**
   * Supprime un article du panier.
   *
   * @param int $itemId Identifiant de la ligne panier
   * @param CartService $cartService Service panier
   * @return void
   */
  public function removeItem(int $itemId, CartService $cartService): void
  {
    $item = $this->findCartItem($itemId);
    $cartService->removeItem($this->cart, $item);
    $this->cart = $cartService->getCartWithItems();
    $this->dispatch('cart-updated');
  }

  /**
   * Vide entièrement le panier.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  public function clearCart(CartService $cartService): void
  {
    $cartService->clear($this->cart);
    $this->cart = $cartService->getCartWithItems();
    $this->dispatch('cart-updated');
  }

  /**
   * Rafraîchit le panier après ajout depuis une autre page.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  #[On('cart-updated')]
  public function refreshCart(CartService $cartService): void
  {
    $this->cart = $cartService->getCartWithItems();
  }

  /**
   * Retrouve une ligne panier par son identifiant.
   *
   * @param int $itemId Identifiant de la ligne
   * @return CartItem Ligne panier
   */
  private function findCartItem(int $itemId): CartItem
  {
    $item = $this->cart->items->firstWhere('id', $itemId);

    if (!$item) {
      throw ValidationException::withMessages([
        'cart' => 'Article introuvable.',
      ]);
    }

    return $item;
  }

  /**
   * Rendu de la page panier.
   *
   * @param CurrencyService $currencyService Service devises
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CurrencyService $currencyService)
  {
    return view('livewire.shop.cart-page', [
      'items' => $this->cart->items,
      'subtotal' => $this->cart->subtotal(),
      'currencyService' => $currencyService,
    ])->layout('layouts.shopwise', [
      'title' => 'Panier — Lialalionne',
    ]);
  }
}
