<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Peuple les catégories produits de la boutique.
 */
class CategorySeeder extends Seeder
{
  /**
   * Crée les catégories soins corporels.
   *
   * @return void
   */
  public function run(): void
  {
    $categories = [
      ['name' => 'Soin du visage', 'description' => 'Crèmes, sérums et soins ciblés pour le visage.', 'sort_order' => 1],
      ['name' => 'Soin du corps', 'description' => 'Laits, baumes et soins nourrissants pour tout le corps.', 'sort_order' => 2],
      ['name' => 'Soin fessier', 'description' => 'Soins liftants et raffermissants pour les fesses.', 'sort_order' => 3],
      ['name' => 'Gommages & exfoliants', 'description' => 'Gommages doux et exfoliants pour une peau lisse.', 'sort_order' => 4],
      ['name' => 'Huiles & baumes', 'description' => 'Huiles végétales et baumes ultra-nourrissants.', 'sort_order' => 5],
    ];

    foreach ($categories as $index => $category) {
      Category::query()->create([
        'name' => $category['name'],
        'slug' => Str::slug($category['name']),
        'description' => $category['description'],
        'is_active' => true,
        'sort_order' => $category['sort_order'],
      ]);
    }
  }
}
