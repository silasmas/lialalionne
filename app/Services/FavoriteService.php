<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductFavorite;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Service de gestion des produits favoris client.
 */
class FavoriteService
{
  /**
   * Bascule l'état favori d'un produit pour l'utilisateur connecté.
   *
   * @param User $user Client connecté
   * @param Product $product Produit cible
   * @return bool True si le produit est maintenant en favori
   */
  public function toggle(User $user, Product $product): bool
  {
    $favorite = ProductFavorite::query()
      ->where('user_id', $user->id)
      ->where('product_id', $product->id)
      ->first();

    if ($favorite) {
      $favorite->delete();

      return false;
    }

    ProductFavorite::query()->create([
      'user_id' => $user->id,
      'product_id' => $product->id,
    ]);

    return true;
  }

  /**
   * Indique si un produit est en favori pour l'utilisateur.
   *
   * @param User $user Client connecté
   * @param Product $product Produit cible
   * @return bool True si favori
   */
  public function isFavorite(User $user, Product $product): bool
  {
    return ProductFavorite::query()
      ->where('user_id', $user->id)
      ->where('product_id', $product->id)
      ->exists();
  }

  /**
   * Retourne les IDs produits favoris pour pré-chargement UI.
   *
   * @param User $user Client connecté
   * @return Collection<int, int> Identifiants produits
   */
  public function favoriteProductIds(User $user): Collection
  {
    return ProductFavorite::query()
      ->where('user_id', $user->id)
      ->pluck('product_id');
  }

  /**
   * Liste paginable des favoris avec produits actifs.
   *
   * @param User $user Client connecté
   * @return Collection<int, Product> Produits favoris
   */
  public function listProducts(User $user): Collection
  {
    return $user->favoriteProducts()
      ->where('is_active', true)
      ->with(['category', 'images'])
      ->orderByDesc('product_favorites.created_at')
      ->get();
  }
}
