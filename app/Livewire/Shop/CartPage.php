<?php

namespace App\Livewire\Shop;

use App\Livewire\Shop\Concerns\DispatchesShopToast;
use App\Models\Cart;
use App\Models\CartItem;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\CurrencyService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * Page panier : liste des articles, quantités et sous-total.
 */
class CartPage extends Component
{
  use DispatchesShopToast;

  public Cart $cart;

  public string $couponCode = '';

  public ?string $appliedCouponCode = null;

  public ?string $appliedCouponLabel = null;

  public float $discountEur = 0;

  /**
   * Charge le panier courant avec ses articles.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  public function mount(CartService $cartService): void
  {
    $this->cart = $cartService->getCartWithItems();
    $this->restoreRememberedCoupon();
  }

  /**
   * Reprend le code promo déjà appliqué en session.
   *
   * @return void
   */
  private function restoreRememberedCoupon(): void
  {
    $remembered = app(CouponService::class)->rememberedCode();

    if (!$remembered) {
      return;
    }

    $this->couponCode = $remembered;
    $this->appliedCouponCode = $remembered;
    $this->refreshCouponDiscount();
  }

  /**
   * Applique un code promo sur le sous-total du panier.
   *
   * @param CouponService $couponService Service codes promo
   * @return void
   */
  public function applyCoupon(CouponService $couponService): void
  {
    $this->resetValidation('couponCode');

    try {
      $coupon = $couponService->validateForCheckout(
        $this->couponCode,
        $this->cart->subtotal()
      );

      $this->appliedCouponCode = $coupon->code;
      $this->appliedCouponLabel = $coupon->name;
      $this->couponCode = $coupon->code;
      $this->discountEur = $couponService->calculateDiscountEur($coupon, $this->cart->subtotal());
      $couponService->rememberAppliedCode($coupon->code);
      $this->dispatchShopToast('Code promo « ' . $coupon->code . ' » appliqué.', 'success');
    } catch (ValidationException $exception) {
      $this->appliedCouponCode = null;
      $this->appliedCouponLabel = null;
      $this->discountEur = 0;
      $couponService->forgetRememberedCode();

      foreach ($exception->errors() as $field => $messages) {
        foreach ($messages as $message) {
          $this->addError($field, $message);
        }
      }
    }
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
      $this->refreshCouponDiscount();
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
    $this->refreshCouponDiscount();
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
    $this->refreshCouponDiscount();
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
    $this->refreshCouponDiscount();
  }

  /**
   * Retire le code promo du panier.
   *
   * @param CouponService $couponService Service codes promo
   * @return void
   */
  public function removeCoupon(CouponService $couponService): void
  {
    $this->couponCode = '';
    $this->appliedCouponCode = null;
    $this->appliedCouponLabel = null;
    $this->discountEur = 0;
    $couponService->forgetRememberedCode();
    $this->resetValidation('couponCode');
  }

  /**
   * Recalcule la remise si un code est encore valable.
   *
   * @return void
   */
  private function refreshCouponDiscount(): void
  {
    if (!$this->appliedCouponCode) {
      $this->discountEur = 0;

      return;
    }

    try {
      $couponService = app(CouponService::class);
      $coupon = $couponService->validateForCheckout(
        $this->appliedCouponCode,
        $this->cart->subtotal()
      );
      $this->appliedCouponLabel = $coupon->name;
      $this->discountEur = $couponService->calculateDiscountEur($coupon, $this->cart->subtotal());
    } catch (ValidationException) {
      $this->appliedCouponCode = null;
      $this->appliedCouponLabel = null;
      $this->discountEur = 0;
      $this->couponCode = '';
      app(CouponService::class)->forgetRememberedCode();
    }
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
    $this->refreshCouponDiscount();

    $subtotal = $this->cart->subtotal();

    return view('livewire.shop.cart-page', [
      'items' => $this->cart->items,
      'subtotal' => $subtotal,
      'estimatedTotal' => max(0, $subtotal - $this->discountEur),
      'currencyService' => $currencyService,
    ])->layout('layouts.shopwise', [
      'title' => 'Panier — Lialalionne',
      'noindex' => true,
    ]);
  }
}
