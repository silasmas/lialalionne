<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;

/**
 * Service de gestion du panier d'achat (session et utilisateur).
 */
class CartService
{
  /**
   * @param StockService $stockService Service de vérification des stocks
   */
  public function __construct(
    private readonly StockService $stockService
  ) {
  }

  /**
   * Récupère ou crée le panier actif pour l'utilisateur ou la session.
   *
   * @param User|null $user Utilisateur connecté
   * @return Cart Panier actif
   */
  public function getOrCreateCart(?User $user = null): Cart
  {
    $user ??= Auth::user();

    if ($user) {
      return Cart::query()->firstOrCreate(['user_id' => $user->id]);
    }

    $sessionId = Session::getId();

    return Cart::query()->firstOrCreate(['session_id' => $sessionId]);
  }

  /**
   * Récupère le panier courant avec ses articles et relations.
   *
   * @param User|null $user Utilisateur connecté
   * @return Cart Panier chargé (peut être vide)
   */
  public function getCartWithItems(?User $user = null): Cart
  {
    $cart = $this->getOrCreateCart($user);
    $cart->load([
      'items.product.images',
      'items.product.category',
      'items.variant',
    ]);

    return $cart;
  }

  /**
   * Compte le nombre total d'articles dans le panier courant.
   *
   * @param User|null $user Utilisateur connecté
   * @return int Somme des quantités
   */
  public function getItemCount(?User $user = null): int
  {
    $user ??= Auth::user();

    $query = Cart::query();

    if ($user) {
      $query->where('user_id', $user->id);
    } else {
      $query->where('session_id', Session::getId());
    }

    $cart = $query->first();

    if (!$cart) {
      return 0;
    }

    return (int) $cart->items()->sum('quantity');
  }

  /**
   * Ajoute un produit au panier après vérification du stock.
   *
   * @param Cart $cart Panier cible
   * @param Product $product Produit à ajouter
   * @param int $quantity Quantité souhaitée
   * @param ProductVariant|null $variant Variante optionnelle
   * @return CartItem Ligne créée ou mise à jour
   */
  public function addItem(
    Cart $cart,
    Product $product,
    int $quantity = 1,
    ?ProductVariant $variant = null
  ): CartItem {
    $quantity = max(1, $quantity);
    $unitPrice = $variant?->price ?? $product->price;

    $existingItem = $cart->items()
      ->where('product_id', $product->id)
      ->where('product_variant_id', $variant?->id)
      ->first();

    $totalQuantity = $quantity + ($existingItem?->quantity ?? 0);

    if (!$this->stockService->isAvailable($product, $totalQuantity, $variant)) {
      throw ValidationException::withMessages([
        'quantity' => 'Stock insuffisant pour cette quantité.',
      ]);
    }

    if ($existingItem) {
      $existingItem->update([
        'quantity' => $totalQuantity,
        'unit_price' => $unitPrice,
      ]);

      return $existingItem->fresh();
    }

    return $cart->items()->create([
      'product_id' => $product->id,
      'product_variant_id' => $variant?->id,
      'quantity' => $quantity,
      'unit_price' => $unitPrice,
    ]);
  }

  /**
   * Met à jour la quantité d'une ligne du panier.
   *
   * @param Cart $cart Panier parent
   * @param CartItem $item Ligne à modifier
   * @param int $quantity Nouvelle quantité
   * @return CartItem|null Ligne mise à jour ou null si supprimée
   */
  public function updateQuantity(Cart $cart, CartItem $item, int $quantity): ?CartItem
  {
    if ($item->cart_id !== $cart->id) {
      throw ValidationException::withMessages([
        'cart' => 'Article introuvable dans le panier.',
      ]);
    }

    if ($quantity <= 0) {
      $this->removeItem($cart, $item);

      return null;
    }

    $product = $item->product ?? Product::query()->findOrFail($item->product_id);
    $variant = $item->variant;

    if (!$this->stockService->isAvailable($product, $quantity, $variant)) {
      throw ValidationException::withMessages([
        'quantity' => 'Stock insuffisant.',
      ]);
    }

    $item->update(['quantity' => $quantity]);

    return $item->fresh();
  }

  /**
   * Supprime une ligne du panier.
   *
   * @param Cart $cart Panier parent
   * @param CartItem $item Ligne à supprimer
   * @return void
   */
  public function removeItem(Cart $cart, CartItem $item): void
  {
    if ($item->cart_id !== $cart->id) {
      throw ValidationException::withMessages([
        'cart' => 'Article introuvable dans le panier.',
      ]);
    }

    $item->delete();
  }

  /**
   * Vide entièrement le panier.
   *
   * @param Cart $cart Panier à vider
   * @return void
   */
  public function clear(Cart $cart): void
  {
    $cart->items()->delete();
  }

  /**
   * Fusionne le panier invité (session) dans le panier utilisateur connecté.
   *
   * @param User $user Utilisateur connecté
   * @return void
   */
  public function mergeGuestCartIntoUser(User $user): void
  {
    $sessionId = Session::getId();
    $guestCart = Cart::query()->where('session_id', $sessionId)->with('items.product', 'items.variant')->first();

    if (!$guestCart || $guestCart->items->isEmpty()) {
      return;
    }

    $userCart = $this->getOrCreateCart($user);

    foreach ($guestCart->items as $item) {
      try {
        $this->addItem($userCart, $item->product, $item->quantity, $item->variant);
      } catch (ValidationException) {
        // Stock insuffisant : on ignore la ligne en conflit
      }
    }

    $guestCart->delete();
  }
}
