<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Str;

/**
 * Génère des SKU uniques pour produits et variantes.
 */
class SkuGenerator
{
  /**
   * Génère un SKU produit unique (préfixe LL + slug + suffixe aléatoire).
   *
   * @param string|null $name Nom du produit (optionnel)
   * @return string SKU généré
   */
  public static function forProduct(?string $name = null): string
  {
    $base = self::slugPart($name, 'PRD');

    do {
      $sku = 'LL-' . $base . '-' . strtoupper(Str::random(4));
    } while (Product::query()->where('sku', $sku)->exists());

    return $sku;
  }

  /**
   * Génère un SKU variante unique, basé sur le produit parent si possible.
   *
   * @param string|null $variantName Nom de la variante
   * @param string|null $productSku SKU du produit parent
   * @return string SKU généré
   */
  public static function forVariant(?string $variantName = null, ?string $productSku = null): string
  {
    $prefix = $productSku
      ? Str::upper(Str::limit(preg_replace('/[^A-Za-z0-9\-]/', '', $productSku) ?? 'LL', 16, ''))
      : 'LL-VAR';
    $part = self::slugPart($variantName, 'VAR');

    do {
      $sku = $prefix . '-' . $part . '-' . strtoupper(Str::random(3));
    } while (
      ProductVariant::query()->where('sku', $sku)->exists()
      || Product::query()->where('sku', $sku)->exists()
    );

    return $sku;
  }

  /**
   * Construit une portion de SKU à partir d'un libellé.
   *
   * @param string|null $label Libellé source
   * @param string $fallback Valeur de repli
   * @return string Portion normalisée
   */
  private static function slugPart(?string $label, string $fallback): string
  {
    $slug = Str::upper(Str::slug($label ?? '', ''));

    if ($slug === '') {
      return $fallback;
    }

    return Str::limit($slug, 10, '');
  }
}
