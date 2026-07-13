<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Faker\Factory as FakerFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Peuple 200 produits répartis sur les catégories avec images placeholder.
 */
class ProductSeeder extends Seeder
{
  private const PRODUCT_COUNT = 200;

  private const PLACEHOLDER_JPEG = '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAX/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFAEBAAAAAAAAAAAAAAAAAAAAAP/EABQRAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhEDEQA/AJ+AAAAAAAAf/9k=';

  /**
   * Crée 200 produits avec variantes optionnelles et image principale.
   *
   * @return void
   */
  public function run(): void
  {
    $faker = FakerFactory::create('fr_FR');
    $categories = Category::query()->get()->keyBy('slug');

    if ($categories->isEmpty()) {
      return;
    }

    $templates = $this->getTemplatesByCategory();

    for ($index = 1; $index <= self::PRODUCT_COUNT; $index++) {
      $category = $categories->values()->get(($index - 1) % $categories->count());
      $categorySlug = $category->slug;
      $prefixes = $templates[$categorySlug] ?? $templates['soin-du-corps'];
      $prefix = $prefixes[array_rand($prefixes)];
      $suffix = $faker->randomElement(['Intense', 'Douceur', 'Premium', 'Bio', 'Expert', 'Éclat', 'Nuit', 'Jour', 'Sensuelle', 'Pure']);
      $name = trim($prefix . ' ' . $suffix . ' ' . $index);
      $slug = Str::slug($name);
      $sku = 'LL-' . strtoupper(substr($categorySlug, 0, 3)) . '-' . str_pad((string) $index, 4, '0', STR_PAD_LEFT);
      $price = $faker->randomFloat(2, 9.9, 59.9);
      $hasDiscount = $faker->boolean(25);
      $compareAt = $hasDiscount ? round($price * 1.2, 2) : null;
      $stock = $faker->numberBetween(0, 120);

      $product = Product::query()->create([
        'category_id' => $category->id,
        'name' => $name,
        'slug' => $slug,
        'sku' => $sku,
        'short_description' => $faker->sentence(12),
        'description' => $faker->paragraphs(3, true),
        'ingredients' => $faker->sentence(20),
        'usage_tips' => $faker->sentence(15),
        'price' => $price,
        'compare_at_price' => $compareAt,
        'stock' => $stock,
        'track_stock' => true,
        'is_active' => true,
        'is_featured' => $faker->boolean(12),
        'weight' => $faker->randomElement([30, 50, 75, 100, 150, 200, 250, 400]),
      ]);

      if ($faker->boolean(35)) {
        $this->createVariants($product, $faker, $price);
      }

      $imagePath = $this->storePlaceholderImage($slug);
      ProductImage::query()->create([
        'product_id' => $product->id,
        'path' => $imagePath,
        'alt_text' => $name,
        'sort_order' => 0,
        'is_primary' => true,
      ]);

      if ($faker->boolean(20)) {
        $illustrationCount = $faker->numberBetween(1, min(3, Product::MAX_ILLUSTRATION_IMAGES));

        for ($imageIndex = 1; $imageIndex <= $illustrationCount; $imageIndex++) {
          ProductImage::query()->create([
            'product_id' => $product->id,
            'path' => $this->storePlaceholderImage($slug . '-ill-' . $imageIndex),
            'alt_text' => $name . ' — illustration ' . $imageIndex,
            'sort_order' => $imageIndex,
            'is_primary' => false,
          ]);
        }
      }
    }
  }

  /**
   * Crée 1 à 2 variantes pour un produit.
   *
   * @param Product $product Produit parent
   * @param \Faker\Generator $faker Générateur Faker
   * @param float $basePrice Prix de base
   * @return void
   */
  private function createVariants(Product $product, \Faker\Generator $faker, float $basePrice): void
  {
    $formats = [
      ['name' => '50 ml', 'factor' => 1.0],
      ['name' => '100 ml', 'factor' => 1.45],
      ['name' => '200 ml', 'factor' => 2.1],
    ];

    $selected = $faker->randomElements($formats, $faker->numberBetween(1, 2));

    foreach ($selected as $format) {
      ProductVariant::query()->create([
        'product_id' => $product->id,
        'name' => $format['name'],
        'sku' => $product->sku . '-' . str_replace(' ', '', $format['name']),
        'price' => round($basePrice * $format['factor'], 2),
        'stock' => $faker->numberBetween(5, 80),
        'is_active' => true,
      ]);
    }
  }

  /**
   * Enregistre une image placeholder dans le disque public.
   *
   * @param string $slug Slug produit pour nommer le fichier
   * @return string Chemin relatif stocké en base
   */
  private function storePlaceholderImage(string $slug): string
  {
    $path = 'products/' . $slug . '.jpg';
    $disk = Storage::disk('public');

    if (!$disk->exists($path)) {
      $disk->put($path, base64_decode(self::PLACEHOLDER_JPEG));
    }

    return $path;
  }

  /**
   * Retourne les préfixes de noms produits par catégorie.
   *
   * @return array<string, list<string>>
   */
  private function getTemplatesByCategory(): array
  {
    return [
      'soin-du-visage' => [
        'Sérum vitamine C', 'Crème hydratante', 'Masque visage', 'Eau micellaire', 'Contour des yeux',
        'Gel nettoyant', 'Tonique apaisant', 'Crème de nuit', 'Fluide matifiant', 'Baume lèvres',
      ],
      'soin-du-corps' => [
        'Lait corporel', 'Baume nourrissant', 'Gel douche', 'Crème mains', 'Soin pieds',
        'Beurre corporel', 'Brume parfumée', 'Savon surgras', 'Lotion tonifiante', 'Crème multi-usages',
      ],
      'soin-fessier' => [
        'Crème liftante', 'Huile sublimatrice', 'Gel raffermissant', 'Soin tonifiant', 'Baume sculptant',
        'Crème bonne mine', 'Huile sèche nacrée', 'Sérum fermeté', 'Gel modelant', 'Crème lissante',
      ],
      'gommages-exfoliants' => [
        'Gommage sucre', 'Exfoliant enzymatique', 'Gommage grain fin', 'Peeling doux', 'Scrub corporel',
        'Gommage visage', 'Exfoliant café', 'Gommage coco', 'Peeling chimique doux', 'Gommage karité',
      ],
      'huiles-baumes' => [
        'Huile d\'argan', 'Baume réparateur', 'Huile de coco', 'Baume multipurpose', 'Huile de jojoba',
        'Baume karité', 'Huile d\'avocat', 'Baume lèvres', 'Huile sèche', 'Baume universel',
      ],
    ];
  }
}
