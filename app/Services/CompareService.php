<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

/**
 * Liste de comparaison produits en session (style Shopwise).
 */
class CompareService
{
  private const SESSION_KEY = 'shop.compare_product_ids';

  private const MAX_ITEMS = 4;

  /**
   * Retourne les identifiants produits en comparaison.
   *
   * @return list<int> IDs produits
   */
  public function ids(): array
  {
    $ids = Session::get(self::SESSION_KEY, []);

    return array_values(array_filter(array_map('intval', $ids)));
  }

  /**
   * Compte les produits en comparaison.
   *
   * @return int Nombre de produits
   */
  public function count(): int
  {
    return count($this->ids());
  }

  /**
   * Charge les produits actifs en comparaison.
   *
   * @return Collection<int, Product> Produits ordonnés
   */
  public function products(): Collection
  {
    $ids = $this->ids();

    if ($ids === []) {
      return collect();
    }

    $products = Product::query()
      ->whereIn('id', $ids)
      ->where('is_active', true)
      ->with(['category', 'images', 'variants'])
      ->get()
      ->keyBy('id');

    return collect($ids)
      ->map(fn (int $id) => $products->get($id))
      ->filter();
  }

  /**
   * Ajoute un produit à la liste de comparaison.
   *
   * @param Product $product Produit à ajouter
   * @return bool True si ajouté
   */
  public function add(Product $product): bool
  {
    $ids = $this->ids();

    if (in_array($product->id, $ids, true)) {
      return false;
    }

    if (count($ids) >= self::MAX_ITEMS) {
      array_shift($ids);
    }

    $ids[] = $product->id;
    Session::put(self::SESSION_KEY, $ids);

    return true;
  }

  /**
   * Ajoute le produit et des produits similaires (même catégorie).
   *
   * @param Product $product Produit principal
   * @param int $similarLimit Nombre max de similaires à ajouter
   * @return list<int> IDs ajoutés
   */
  public function addWithSimilar(Product $product, int $similarLimit = 2): array
  {
    $added = [];

    if ($this->add($product)) {
      $added[] = $product->id;
    }

    if ($product->category_id === null || $similarLimit <= 0) {
      return $added;
    }

    $similar = Product::query()
      ->where('is_active', true)
      ->where('category_id', $product->category_id)
      ->where('id', '!=', $product->id)
      ->whereNotIn('id', $this->ids())
      ->inRandomOrder()
      ->limit($similarLimit)
      ->get();

    foreach ($similar as $similarProduct) {
      if ($this->add($similarProduct)) {
        $added[] = $similarProduct->id;
      }
    }

    return $added;
  }

  /**
   * Retire un produit de la comparaison.
   *
   * @param int $productId Identifiant produit
   * @return void
   */
  public function remove(int $productId): void
  {
    $ids = array_values(array_filter(
      $this->ids(),
      fn (int $id) => $id !== $productId
    ));

    Session::put(self::SESSION_KEY, $ids);
  }

  /**
   * Vide la liste de comparaison.
   *
   * @return void
   */
  public function clear(): void
  {
    Session::forget(self::SESSION_KEY);
  }
}
