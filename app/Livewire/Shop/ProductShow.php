<?php

namespace App\Livewire\Shop;

use App\Enums\OrderStatus;
use App\Livewire\Shop\Concerns\InteractsWithProductCard;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Review;
use App\Services\CartService;
use App\Services\CurrencyService;
use App\Services\FavoriteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
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

  public int $reviewRating = 5;

  public string $reviewTitle = '';

  public string $reviewComment = '';

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

    $product->load([
      'category',
      'images',
      'variants' => fn ($q) => $q->where('is_active', true),
      'relatedProducts.images',
      'relatedProducts.variants',
      'inverseRelatedProducts.images',
      'inverseRelatedProducts.variants',
    ]);

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
   * Ajoute le produit courant et toute sa gamme au panier.
   *
   * @param CartService $cartService Service panier
   * @return void
   */
  public function addRangeToCart(CartService $cartService): void
  {
    $this->cartMessage = null;
    $this->resetErrorBag();

    $companions = $this->product->rangeCompanions();
    $toAdd = collect([$this->product])->merge($companions);
    $addedCount = 0;

    $cart = $cartService->getOrCreateCart();

    foreach ($toAdd as $product) {
      if (!$product->is_active) {
        continue;
      }

      $variant = $product->variants->firstWhere('is_active', true)
        ?? $product->variants->first();

      if ($variant && (int) $variant->stock <= 0 && $product->track_stock) {
        continue;
      }

      if (!$product->isInStock() && !$variant) {
        continue;
      }

      try {
        $cartService->addItem($cart, $product, 1, $variant);
        $addedCount++;
      } catch (ValidationException) {
        continue;
      }
    }

    if ($addedCount === 0) {
      $message = 'Aucun produit de cette gamme n\'est disponible pour le moment.';
      $this->addError('cart', $message);
      $this->dispatchShopToast($message, 'error');

      return;
    }

    $this->cartMessage = $addedCount === 1
      ? 'Produit ajouté au panier.'
      : $addedCount . ' produits de la gamme ont été ajoutés au panier.';
    $this->dispatch('cart-updated');
    $this->dispatchShopToast($this->cartMessage, 'success');
  }

  /**
   * Indique si le client a acheté ce produit dans une commande honorée.
   *
   * @return bool True si achat vérifié
   */
  private function hasVerifiedPurchase(): bool
  {
    if (!Auth::check()) {
      return false;
    }

    return Auth::user()->orders()
      ->whereIn('status', [OrderStatus::Paid, OrderStatus::Processing, OrderStatus::Shipped, OrderStatus::Delivered])
      ->whereHas('items', fn ($q) => $q->where('product_id', $this->product->id))
      ->exists();
  }

  /**
   * Soumet un avis client sur le produit (publié après modération).
   *
   * @return void
   */
  public function submitReview(): void
  {
    if (!Auth::check()) {
      $this->dispatchShopToast('Connectez-vous pour laisser un avis.', 'error');

      return;
    }

    $this->validate([
      'reviewRating' => ['required', 'integer', 'min:1', 'max:5'],
      'reviewTitle' => ['nullable', 'string', 'max:255'],
      'reviewComment' => ['required', 'string', 'max:2000'],
    ], [
      'reviewComment.required' => 'Merci de partager votre avis en quelques mots.',
    ], [
      'reviewRating' => 'note',
      'reviewComment' => 'commentaire',
    ]);

    $alreadyReviewed = Review::query()
      ->where('product_id', $this->product->id)
      ->where('user_id', Auth::id())
      ->exists();

    if ($alreadyReviewed) {
      $this->dispatchShopToast('Vous avez déjà laissé un avis sur ce produit.', 'error');

      return;
    }

    Review::query()->create([
      'product_id' => $this->product->id,
      'user_id' => Auth::id(),
      'rating' => $this->reviewRating,
      'title' => $this->reviewTitle ?: null,
      'comment' => $this->reviewComment,
      'is_verified_purchase' => $this->hasVerifiedPurchase(),
      'is_approved' => false,
    ]);

    $this->reset(['reviewRating', 'reviewTitle', 'reviewComment']);
    $this->reviewRating = 5;

    $this->dispatchShopToast('Merci ! Votre avis sera publié après validation.', 'success');
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
      'rangeProducts' => $this->product->rangeCompanions(),
      'images' => $this->product->images
        ->sortBy([
          ['is_primary', 'desc'],
          ['sort_order', 'asc'],
        ])
        ->values(),
      'reviews' => $this->product->approvedReviews()
        ->with('user:id,name')
        ->latest()
        ->get(),
      'userHasReviewed' => Auth::check()
        ? Review::query()->where('product_id', $this->product->id)->where('user_id', Auth::id())->exists()
        : false,
    ])->layout('layouts.shopwise', [
      'title' => $this->product->name . ' — Lialalionne',
      'metaDescription' => Str::limit(
        $this->product->short_description ?? $this->product->description ?? $this->product->name,
        160
      ),
      'ogImage' => $this->product->primaryImageUrl(),
      'ogType' => 'product',
      'jsonLd' => [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => $this->product->name,
        'description' => $this->product->short_description ?? $this->product->description,
        'image' => array_values(array_filter([$this->product->primaryImageUrl()])),
        'sku' => $this->product->sku,
        'category' => $this->product->category?->name,
        'offers' => [
          '@type' => 'Offer',
          'url' => route('products.show', $this->product),
          'priceCurrency' => app(CurrencyService::class)->selectedCurrency(),
          'price' => number_format(
            app(CurrencyService::class)->convertFromEur((float) $this->product->price),
            2,
            '.',
            ''
          ),
          'availability' => $this->product->isInStock()
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock',
        ],
        ...($this->product->reviewsCount() > 0 ? [
          'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => (string) $this->product->averageRating(),
            'reviewCount' => (string) $this->product->reviewsCount(),
          ],
        ] : []),
      ],
    ]);
  }
}
