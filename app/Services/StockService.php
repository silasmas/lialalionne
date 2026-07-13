<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;

/**
 * Service de vérification et mise à jour des stocks produits.
 */
class StockService
{
  /**
   * Vérifie si la quantité demandée est disponible en stock.
   *
   * @param Product $product Produit concerné
   * @param int $quantity Quantité demandée
   * @param ProductVariant|null $variant Variante optionnelle
   * @return bool True si le stock est suffisant
   */
  public function isAvailable(
    Product $product,
    int $quantity,
    ?ProductVariant $variant = null
  ): bool {
    if ($variant) {
      return $variant->stock >= $quantity;
    }

    if (!$product->track_stock) {
      return true;
    }

    return $product->stock >= $quantity;
  }

  /**
   * Décrémente le stock après une commande validée.
   *
   * @param Product $product Produit concerné
   * @param int $quantity Quantité vendue
   * @param ProductVariant|null $variant Variante optionnelle
   * @return void
   */
  public function decrement(
    Product $product,
    int $quantity,
    ?ProductVariant $variant = null
  ): void {
    if ($variant) {
      $variant->decrement('stock', $quantity);

      return;
    }

    if ($product->track_stock) {
      $product->decrement('stock', $quantity);
    }
  }
}
