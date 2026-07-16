<?php

namespace App\Livewire\Shop;

use App\Livewire\Shop\Concerns\InteractsWithProductCard;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\FavoriteService;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Fiche produit détaillée avec galerie Shopwise et sélection de variante.
 */
class ProductShow extends Component
{
  use InteractsWithProductCard;

  public Product $product;

  public ?int $selectedVariantId = null;

  public int $quantity = 1;

  public ?string $cartMessage = null;

  /**
   * Charge le produit actif et ses relations.
   *
   * @param Product $product Produit résolu par slug
   * @return void
   */
  public function mount(Product $product): void
  {
    if (!$product->is_active) {
      abort(404);
    }

    $product->load(['category', 'images', 'variants' => fn ($q) => $q->where('is_active', true)]);

    $this->product = $product;

    if ($product->variants->isNotEmpty()) {
      $this->selectedVariantId = $product->variants->first()->id;
    }
  }

  /**
   * Variante actuellement sélectionnée.
   *
   * @return ProductVariant|null Variante ou null
   */
  public function getSelectedVariantProperty(): ?ProductVariant
  {
    if (!$this->selectedVariantId) {
      return null;
    }

    return $this->product->variants->firstWhere('id', $this->selectedVariantId);
  }

  /**
   * Prix affiché selon la variante ou le produit de base.
   *
   * @return float Montant en euros (base de conversion)
   */
  public function getCurrentPriceProperty(): float
  {
    if ($variant = $this->selectedVariant) {
      return (float) $variant->price;
    }

    return (float) $this->product->price;
  }

  /**
   * Indique si le produit/variante sélectionné est disponible.
   *
   * @return bool True si en stock
   */
  public function getIsAvailableProperty(): bool
  {
    if ($variant = $this->selectedVariant) {
      return $variant->stock > 0;
    }

    return $this->product->isInStock();
  }

  /**
   * Sélectionne une variante produit.
   *
   * @param int $variantId Identifiant variante
   * @return void
   */
  public function selectVariant(int $variantId): void
  {
    $this->selectedVariantId = $variantId;
  }

  /**
   * Diminue la quantité (minimum 1).
   *
   * @return void
   */
  public function decrementQuantity(): void
  {
    $this->quantity = max(1, $this->quantity - 1);
  }

  /**
   * Augmente la quantité.
   *
   * @return void
   */
  public function incrementQuantity(): void
  {
    $this->quantity++;
  }

  /**
   * Ajoute le produit sélectionné au panier.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  public function addToCart(CartService $cartService): void
  {
    $this->cartMessage = null;
    $this->resetErrorBag();

    if (!$this->isAvailable) {
      $message = 'Ce produit n\'est pas disponible.';
      $this->addError('cart', $message);
      $this->dispatchShopToast($message, 'error');

      return;
    }

    try {
      $cart = $cartService->getOrCreateCart();
      $cartService->addItem(
        $cart,
        $this->product,
        $this->quantity,
        $this->selectedVariant
      );

      $this->cartMessage = 'Produit ajouté au panier.';
      $this->dispatch('cart-updated');
      $this->dispatchShopToast('Produit ajouté au panier.', 'success');
    } catch (ValidationException $exception) {
      $message = $exception->errors()['quantity'][0] ?? 'Impossible d\'ajouter au panier.';
      $this->addError('cart', $message);
      $this->dispatchShopToast($message, 'error');
    }
  }

  /**
   * Rendu de la fiche produit Shopwise.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(FavoriteService $favoriteService)
  {
    $this->loadFavoriteIds($favoriteService);

    return view('livewire.shop.product-show', [
      'images' => $this->product->images
        ->sortBy([
          ['is_primary', 'desc'],
          ['sort_order', 'asc'],
        ])
        ->values(),
    ])->layout('layouts.shopwise', [
      'title' => $this->product->name . ' — Lialalionne',
    ]);
  }
}
