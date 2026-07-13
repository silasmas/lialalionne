<?php

namespace App\Livewire\Shop;

use App\Models\Product;
use App\Services\CartService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Bouton d'ajout rapide au panier depuis catalogue ou accueil.
 */
class AddToCartButton extends Component
{
  public Product $product;

  public ?string $message = null;

  /**
   * Ajoute le produit (ou sa première variante) au panier.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  public function addToCart(CartService $cartService): void
  {
    $this->message = null;
    $this->resetErrorBag();

    $product = $this->product->load(['variants' => fn ($q) => $q->where('is_active', true)]);

    if (!$product->isInStock() && $product->variants->every(fn ($v) => $v->stock <= 0)) {
      $this->addError('cart', 'Produit indisponible.');

      return;
    }

    $variant = $product->variants->firstWhere('stock', '>', 0)
      ?? $product->variants->first();

    if ($product->variants->count() > 1 && !$variant) {
      $this->redirect(route('products.show', $product), navigate: true);

      return;
    }

    try {
      $cart = $cartService->getOrCreateCart();
      $cartService->addItem($cart, $product, 1, $variant);
      $this->message = 'Ajouté';
      $this->dispatch('cart-updated');
    } catch (ValidationException $exception) {
      $this->addError('cart', $exception->errors()['quantity'][0] ?? 'Stock insuffisant.');
    }
  }

  /**
   * Rendu du bouton panier rapide.
   *
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render()
  {
    return view('livewire.shop.add-to-cart-button');
  }
}
