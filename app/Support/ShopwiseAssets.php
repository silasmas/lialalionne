<?php

namespace App\Support;

/**
 * Chemins vers les assets du template Shopwise (images produit de secours).
 */
class ShopwiseAssets
{
  /**
   * Retourne l'URL d'une image produit du template (1 à 12).
   *
   * @param int $seed Identifiant ou index pour choisir l'image
   * @return string URL publique de l'image
   */
  public static function productImageUrl(int $seed): string
  {
    $imageIndex = (($seed - 1) % 12) + 1;

    return asset('shopwise/assets/images/product_img' . $imageIndex . '.jpg');
  }

  /**
   * Retourne la liste des 12 images produit du template.
   *
   * @return list<string> URLs des images
   */
  public static function productImageList(): array
  {
    return collect(range(1, 12))
      ->map(fn (int $index): string => asset('shopwise/assets/images/product_img' . $index . '.jpg'))
      ->all();
  }
}
