<?php

namespace App\Livewire\Shop\Concerns;

use App\Models\Product;
use App\Services\CartService;
use App\Services\FavoriteService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

/**
 * Actions panier et favoris pour les cartes produit (accueil, catalogue).
 */
trait InteractsWithProductCard
{
  use DispatchesShopToast;

  /** @var list<int> Identifiants produits favoris du client connecté. */
  public array $favoriteIds = [];

  /** @var int|null Dernier produit ajouté au panier (feedback UI). */
  public ?int $cartAddedProductId = null;

  /**
   * Charge les IDs favoris pour l'affichage des cœurs.
   *
   * @param FavoriteService $favoriteService Service favoris
   * @return void
   */
  protected function loadFavoriteIds(FavoriteService $favoriteService): void
  {
    if (!Auth::check()) {
      $this->favoriteIds = [];

      return;
    }

    $this->favoriteIds = $favoriteService
      ->favoriteProductIds(Auth::user())
      ->all();
  }

  /**
   * Ajoute un produit au panier depuis une carte (1ère variante disponible).
   *
   * @param int $productId Identifiant produit
   * @param CartService $cartService Service panier
   * @return mixed Redirection fiche produit si variantes multiples
   */
  public function addProductToCart(int $productId, CartService $cartService)
  {
    $this->cartAddedProductId = null;

    $product = Product::query()
      ->where('is_active', true)
      ->with(['variants' => fn ($q) => $q->where('is_active', true)])
      ->find($productId);

    if (!$product) {
      $this->dispatchShopToast('Produit introuvable.', 'error');

      return null;
    }

    if (!$product->isInStock() && $product->variants->every(fn ($v) => $v->stock <= 0)) {
      $this->dispatchShopToast('Ce produit n\'est pas disponible.', 'error');

      return null;
    }

    $variant = $product->variants->firstWhere('stock', '>', 0)
      ?? $product->variants->first();

    try {
      $cart = $cartService->getOrCreateCart();
      $cartService->addItem($cart, $product, 1, $variant);
      $this->cartAddedProductId = $productId;
      $this->dispatch('cart-updated');
      $this->dispatchShopToast('Produit ajouté au panier.', 'success');
    } catch (ValidationException $exception) {
      $message = $exception->errors()['quantity'][0] ?? 'Stock insuffisant.';
      $this->dispatchShopToast($message, 'error');
    }

    return null;
  }

  /**
   * Bascule le favori d'un produit ou redirige vers la connexion.
   *
   * @param int $productId Identifiant produit
   * @param FavoriteService $favoriteService Service favoris
   * @return mixed Redirection connexion si invité
   */
  public function toggleProductFavorite(int $productId, FavoriteService $favoriteService)
  {
    if (!Auth::check()) {
      $this->dispatchShopToast('Connectez-vous pour gérer vos favoris.', 'error');

      return $this->redirect(route('account.login'), navigate: true);
    }

    $product = Product::query()->find($productId);

    if (!$product) {
      $this->dispatchShopToast('Produit introuvable.', 'error');

      return null;
    }

    $wasFavorite = in_array($productId, $this->favoriteIds, true);
    $favoriteService->toggle(Auth::user(), $product);
    $this->loadFavoriteIds($favoriteService);

    $this->dispatchShopToast(
      $wasFavorite ? 'Produit retiré des favoris.' : 'Produit ajouté aux favoris.',
      'success'
    );

    return null;
  }

  /** @var int|null Produit affiché dans la modale aperçu rapide. */
  public ?int $quickViewProductId = null;

  /** @var bool Affiche la modale de comparaison. */
  public bool $showCompareModal = false;

  /**
   * Ouvre la modale d'aperçu rapide pour un produit.
   *
   * @param int $productId Identifiant produit
   * @return void
   */
  public function openQuickView(int $productId): void
  {
    $this->showCompareModal = false;
    $this->quickViewProductId = $productId;
  }

  /**
   * Ferme la modale d'aperçu rapide.
   *
   * @return void
   */
  public function closeQuickView(): void
  {
    $this->quickViewProductId = null;
  }

  /**
   * Ferme la modale de comparaison.
   *
   * @return void
   */
  public function closeCompareModal(): void
  {
    $this->showCompareModal = false;
  }

  /**
   * Retourne le produit chargé pour l'aperçu rapide.
   *
   * @return Product|null Produit ou null
   */
  public function getQuickViewProductProperty(): ?Product
  {
    if ($this->quickViewProductId === null) {
      return null;
    }

    return Product::query()
      ->where('is_active', true)
      ->with(['category', 'images', 'variants' => fn ($q) => $q->where('is_active', true)])
      ->find($this->quickViewProductId);
  }

  public function getCompareProductsProperty(): \Illuminate\Support\Collection
  {
    return app(\App\Services\CompareService::class)->products();
  }

  /**
   * Ajoute un produit à la comparaison et ouvre la modale.
   *
   * @param int $productId Identifiant produit
   * @param \App\Services\CompareService $compareService Service comparaison
   * @return void
   */
  public function addProductToCompare(int $productId, \App\Services\CompareService $compareService): void
  {
    $product = Product::query()
      ->where('is_active', true)
      ->find($productId);

    if (!$product) {
      $this->dispatchShopToast('Produit introuvable.', 'error');

      return;
    }

    $compareService->addWithSimilar($product);
    $this->closeQuickView();
    $this->showCompareModal = true;
    $this->dispatchShopToast('Produit ajouté à la comparaison.', 'success');
  }

  /**
   * Retire un produit de la modale comparer.
   *
   * @param int $productId Identifiant produit
   * @param \App\Services\CompareService $compareService Service comparaison
   * @return void
   */
  public function removeFromCompareModal(int $productId, \App\Services\CompareService $compareService): void
  {
    $compareService->remove($productId);

    if ($compareService->count() === 0) {
      $this->showCompareModal = false;
    }
  }
}
