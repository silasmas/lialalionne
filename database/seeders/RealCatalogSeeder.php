<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Remplace le catalogue de démonstration par le vrai catalogue
 * "Chez Lia — La Lionne" (Lia's Secret + produits revendus DUOZI/Yokebe),
 * avec les vraies photos produits fournies par la cliente.
 */
class RealCatalogSeeder extends Seeder
{
  /**
   * Supprime les 200 produits de démo et crée le catalogue réel.
   *
   * @return void
   */
  public function run(): void
  {
    $this->deleteDemoCatalog();

    $categories = Category::query()->get()->keyBy('slug');

    $minceur = $categories->get('minceur-complements') ?? Category::query()->create([
      'name' => 'Minceur & compléments',
      'slug' => 'minceur-complements',
      'description' => 'Boissons, thés et compléments pour accompagner silhouette et perte de poids.',
      'is_active' => true,
      'sort_order' => 6,
    ]);

    $corps = $categories->get('soin-du-corps');
    $fessier = $categories->get('soin-fessier');
    $gommages = $categories->get('gommages-exfoliants');
    $huiles = $categories->get('huiles-baumes');

    $products = [
      [
        'category_id' => $minceur->id,
        'name' => 'La Purge Fessier',
        'sku' => 'CL-MIN-0001',
        'short_description' => "Boisson détox 100% naturelle pour un transit régulé et un fessier plus uni.",
        'description' => "La Purge Fessier est bien plus qu'un simple nettoyage : c'est une vraie recette pour ton corps. Formulée pour t'aider à te sentir plus légère, elle accompagne le transit et prépare la peau du fessier à mieux recevoir les soins raffermissants de la gamme Chez Lia.\n\nÀ prendre en cure, elle s'intègre facilement dans une routine minceur globale aux côtés des autres produits de la marque.",
        'ingredients' => "Extraits de plantes 100% naturels (voir étiquette produit).",
        'usage_tips' => "1 à 2 prises par jour, de préférence le matin à jeun. Bien agiter avant emploi.",
        'price' => 18.00,
        'compare_at_price' => null,
        'stock' => 40,
        'is_featured' => true,
        'is_new' => false,
        'is_seasonal' => false,
        'weight' => 250,
        'images' => ['la-purge-fessier.jpg', 'la-purge-fessier-ill-1.jpg'],
      ],
      [
        'category_id' => $fessier->id,
        'name' => 'Crème Bio Maca Vitesse++',
        'sku' => 'CL-FES-0002',
        'short_description' => "Crème fessier intensément volumatrice à base de Maca rouge et noire.",
        'description' => "Sa texture légère et son agréable parfum cacao pénètrent rapidement et laissent sur la peau une sensation de fraîcheur et de tonicité. Formulée à base de Maca rouge et noire, reconnue pour ses vertus raffermissantes.\n\nUn geste beauté quotidien à intégrer dans une routine complète, en complément du gommage et du sirop Lia's Secret.",
        'ingredients' => "Maca rouge, Maca noire, beurre de cacao, actifs volumateurs naturels.",
        'usage_tips' => "Appliquer matin et soir après le bain sur les zones ciblées. Masser énergiquement jusqu'à absorption complète.",
        'price' => 22.00,
        'compare_at_price' => 26.00,
        'stock' => 35,
        'is_featured' => true,
        'is_new' => false,
        'is_seasonal' => true,
        'weight' => 200,
        'images' => ['creme-bio-maca-vitesse.jpg', 'creme-bio-maca-vitesse-ill-1.jpg', 'creme-bio-maca-vitesse-ill-2.jpg'],
      ],
      [
        'category_id' => $fessier->id,
        'name' => 'Boule de Neige — Suppositoires fessier bombé',
        'sku' => 'CL-FES-0003',
        'short_description' => "Suppositoires pour un fessier bombé, à base de graines de courge et huile végétale.",
        'description' => "Boule de Neige est un soin ciblé pensé pour sculpter et raffermir le fessier de l'intérieur. Sa formule aux graines de courge et à l'huile végétale agit en complément des soins topiques de la gamme.",
        'ingredients' => "Graines de courge, huile végétale dégraissée.",
        'usage_tips' => "Usage rectal uniquement. Ne pas dépasser la dose recommandée. Conserver au frais, à l'abri de la lumière.",
        'price' => 20.00,
        'compare_at_price' => null,
        'stock' => 30,
        'is_featured' => false,
        'is_new' => false,
        'is_seasonal' => false,
        'weight' => 100,
        'images' => ['boule-de-neige-suppositoire.jpg'],
      ],
      [
        'category_id' => $minceur->id,
        'name' => '14 Day Flat Tummy Tea',
        'sku' => 'CL-MIN-0004',
        'short_description' => "Thé détox 100% naturel, cure de 14 jours, sans effet diarrhéique.",
        'description' => "Un thé 100% naturel formulé pour soulager les ballonnements, détoxifier l'organisme, accélérer la perte de poids et booster l'énergie au quotidien — sans provoquer de diarrhée. 30 sachets pour une cure complète de 14 jours.",
        'ingredients' => "Mélange d'herbes 100% naturelles.",
        'usage_tips' => "1 sachet infusé par jour pendant 14 jours, de préférence le matin.",
        'price' => 15.00,
        'compare_at_price' => null,
        'stock' => 50,
        'is_featured' => true,
        'is_new' => true,
        'is_seasonal' => false,
        'weight' => 60,
        'images' => ['14-day-flat-tummy-tea.jpg', '14-day-flat-tummy-tea-ill-1.jpg', '14-day-flat-tummy-tea-ill-2.jpg', '14-day-flat-tummy-tea-ill-3.jpg'],
      ],
      [
        'category_id' => $huiles->id,
        'name' => 'Fat Burning Oil — Flat Tummy',
        'sku' => 'CL-HUI-0005',
        'short_description' => "Huile brûle-graisse raffermissante pour le ventre, force maximale.",
        'description' => "Une huile ciblée qui aide à brûler les graisses du ventre, booste le métabolisme et raffermit la peau. Un allié quotidien à associer au 14 Day Flat Tummy Tea et au Flat Tummy Tablet pour une routine minceur complète.",
        'ingredients' => "Huiles végétales actives (voir étiquette produit).",
        'usage_tips' => "Masser matin et soir sur le ventre en mouvements circulaires jusqu'à absorption complète.",
        'price' => 14.00,
        'compare_at_price' => null,
        'stock' => 45,
        'is_featured' => false,
        'is_new' => true,
        'is_seasonal' => false,
        'weight' => 60,
        'images' => ['fat-burning-oil-flat-tummy.jpg'],
      ],
      [
        'category_id' => $minceur->id,
        'name' => 'Flat Tummy Tablet',
        'sku' => 'CL-MIN-0006',
        'short_description' => "Complément alimentaire ventre plat, effet coupe-faim naturel.",
        'description' => "Formulé aux extraits de feuille de lotus, graine de guarana, thé vert et Garcinia Cambogia, Flat Tummy Tablet aide à réduire l'appétit, brûler les graisses abdominales et soutenir une perte de poids naturelle.",
        'ingredients' => "Extrait de feuille de lotus, graine de guarana, thé vert Senna, extrait de gingembre, citronnelle, Garcinia Cambogia, fenouil, chardon-marie.",
        'usage_tips' => "Prendre 2 à 4 comprimés par jour, 15 à 30 minutes avant les repas ou à croquer le matin. Déconseillé aux femmes enceintes/allaitantes.",
        'price' => 19.00,
        'compare_at_price' => null,
        'stock' => 40,
        'is_featured' => false,
        'is_new' => true,
        'is_seasonal' => false,
        'weight' => 90,
        'images' => ['flat-tummy-tablet.jpg', 'flat-tummy-tablet-ill-1.jpg', 'flat-tummy-tablet-ill-2.jpg'],
      ],
      [
        'category_id' => $corps->id,
        'name' => "Sirop Lia's Secret",
        'sku' => 'CL-COR-0007',
        'short_description' => "Soin fluide 100% naturel de la gamme Lia's Secret.",
        'description' => "Le Sirop Lia's Secret complète la routine corps de la gamme : à utiliser en association avec la Crème et le Gommage Lia's Secret pour une peau nourrie et sublimée au quotidien.",
        'ingredients' => "Formule 100% naturelle (voir étiquette produit).",
        'usage_tips' => "Suivre les indications figurant sur l'étiquette du flacon.",
        'price' => 16.00,
        'compare_at_price' => null,
        'stock' => 40,
        'is_featured' => true,
        'is_new' => false,
        'is_seasonal' => true,
        'weight' => 200,
        'images' => [
          'lias-secret-sirop.jpg',
          'lias-secret-sirop-ill-1.jpg',
          'lias-secret-sirop-ill-2.jpg',
          'lias-secret-sirop-ill-3.jpg',
          'lias-secret-sirop-ill-4.jpg',
        ],
        'variants' => [
          ['name' => 'Petit format', 'factor' => 1.0],
          ['name' => 'Grand format', 'factor' => 1.5],
        ],
      ],
      [
        'category_id' => $corps->id,
        'name' => "Crème Lia's Secret",
        'sku' => 'CL-COR-0008',
        'short_description' => "Crème corps 100% naturelle de la gamme Lia's Secret.",
        'description' => "Une crème onctueuse pensée pour nourrir et sublimer la peau au quotidien, en complément du Sirop et du Gommage Lia's Secret.",
        'ingredients' => "Formule 100% naturelle (voir étiquette produit).",
        'usage_tips' => "Appliquer quotidiennement sur peau propre, en massant jusqu'à absorption complète.",
        'price' => 18.00,
        'compare_at_price' => null,
        'stock' => 35,
        'is_featured' => false,
        'is_new' => false,
        'is_seasonal' => true,
        'weight' => 150,
        'images' => ['lias-secret-creme.jpg', 'lias-secret-creme-ill-1.jpg'],
      ],
      [
        'category_id' => $gommages->id,
        'name' => "Gommage Lia's Secret",
        'sku' => 'CL-GOM-0009',
        'short_description' => "Gommage corps 100% naturel de la gamme Lia's Secret.",
        'description' => "Un gommage doux qui élimine les cellules mortes et prépare la peau à mieux recevoir la Crème et le Sirop Lia's Secret, pour un résultat lisse et éclatant.",
        'ingredients' => "Formule 100% naturelle (voir étiquette produit).",
        'usage_tips' => "1 à 2 fois par semaine, sur peau humide, en massant délicatement puis en rinçant.",
        'price' => 15.00,
        'compare_at_price' => null,
        'stock' => 35,
        'is_featured' => false,
        'is_new' => false,
        'is_seasonal' => true,
        'weight' => 150,
        'images' => ['lias-secret-gommage.jpg'],
      ],
      [
        'category_id' => $fessier->id,
        'name' => "Suppositoire Lia's Secret",
        'sku' => 'CL-FES-0010',
        'short_description' => "Soin ciblé fessier 100% naturel de la gamme Lia's Secret.",
        'description' => "Un soin complémentaire de la routine fessier Lia's Secret, à associer à la Crème Bio Maca et au Sirop pour un résultat visible.",
        'ingredients' => "Formule 100% naturelle (voir étiquette produit).",
        'usage_tips' => "Usage rectal uniquement. Suivre les indications figurant sur l'étiquette.",
        'price' => 20.00,
        'compare_at_price' => null,
        'stock' => 30,
        'is_featured' => false,
        'is_new' => false,
        'is_seasonal' => false,
        'weight' => 100,
        'images' => ['lias-secret-suppositoire.jpg'],
        'variants' => [
          ['name' => 'Petit pot', 'factor' => 1.0],
          ['name' => 'Grand pot', 'factor' => 1.4],
        ],
      ],
      [
        'category_id' => $minceur->id,
        'name' => 'Flat Tummy — Garcinia Cambogia Tablet (Duozi)',
        'sku' => 'DZ-MIN-0011',
        'short_description' => "Complément Garcinia Cambogia, force maximale — 60 comprimés.",
        'description' => "Formule force maximale à la Garcinia Cambogia : coupe-faim, brûle-graisses ventre, booste le métabolisme et raffermit la peau. Boîte de 60 comprimés.",
        'ingredients' => "Extrait de Garcinia Cambogia et complexe minceur (voir étiquette produit).",
        'usage_tips' => "Suivre la posologie indiquée sur l'emballage. Conserver au sec, hors de portée des enfants.",
        'price' => 17.00,
        'compare_at_price' => null,
        'stock' => 25,
        'is_featured' => false,
        'is_new' => true,
        'is_seasonal' => false,
        'weight' => 90,
        'images' => ['flat-tummy-garcinia-cambogia.jpg'],
      ],
      [
        'category_id' => $fessier->id,
        'name' => 'Coffret Supreme Curvy Weight Gain + 15 Days Booty Curves (Duozi)',
        'sku' => 'DZ-FES-0012',
        'short_description' => "Coffret huile + comprimés pour galbe des hanches et du fessier, cliniquement approuvé.",
        'description' => "Un coffret complet associant l'huile Supreme Curvy Weight Gain (30 ml x 10 flacons) et les comprimés 15 Days Booty Curves Extreme : renforce les hanches, élimine les vergetures et la cellulite, pour des résultats visibles sous 30 jours.",
        'ingredients' => "Voir étiquettes des produits inclus dans le coffret.",
        'usage_tips' => "Suivre le protocole d'utilisation détaillé sur l'emballage du coffret.",
        'price' => 45.00,
        'compare_at_price' => 55.00,
        'stock' => 15,
        'is_featured' => true,
        'is_new' => false,
        'is_seasonal' => true,
        'weight' => 500,
        'images' => ['coffret-supreme-curvy-booty-curves.jpg'],
      ],
      [
        'category_id' => $minceur->id,
        'name' => 'Yokebe — Sirop prise de poids',
        'sku' => 'YK-MIN-0013',
        'short_description' => "Sirop fruité 100% naturel pour accompagner un objectif de prise de poids.",
        'description' => "Un sirop au goût fruité (ananas, mangue, kiwi, grenade, banane) formulé pour accompagner un objectif de prise de poids saine.",
        'ingredients' => "Extraits de fruits, formule 100% naturelle.",
        'usage_tips' => "Suivre les indications figurant sur l'étiquette du flacon.",
        'price' => 16.00,
        'compare_at_price' => null,
        'stock' => 25,
        'is_featured' => false,
        'is_new' => true,
        'is_seasonal' => false,
        'weight' => 250,
        'images' => ['yokebe-sirop-prise-de-poids.jpg'],
      ],
    ];

    foreach ($products as $data) {
      $images = $data['images'];
      $variants = $data['variants'] ?? null;
      unset($data['images'], $data['variants']);

      $data['slug'] = Str::slug($data['name']);
      $data['track_stock'] = true;
      $data['is_active'] = true;

      $product = Product::query()->updateOrCreate(
        ['sku' => $data['sku']],
        $data
      );

      $product->images()->delete();
      $product->variants()->delete();

      foreach ($images as $index => $filename) {
        ProductImage::query()->create([
          'product_id' => $product->id,
          'path' => 'products/' . $filename,
          'alt_text' => $data['name'],
          'sort_order' => $index,
          'is_primary' => $index === 0,
        ]);
      }

      if ($variants) {
        foreach ($variants as $variant) {
          ProductVariant::query()->create([
            'product_id' => $product->id,
            'name' => $variant['name'],
            'sku' => $product->sku . '-' . Str::slug($variant['name']),
            'price' => round((float) $data['price'] * $variant['factor'], 2),
            'stock' => 20,
            'is_active' => true,
          ]);
        }
      }
    }

    $this->linkRange(['CL-COR-0007', 'CL-COR-0008', 'CL-GOM-0009', 'CL-FES-0010']);
    $this->linkRange(['CL-MIN-0004', 'CL-HUI-0005', 'CL-MIN-0006']);
    $this->linkRange(['CL-FES-0002', 'CL-FES-0003', 'CL-MIN-0001']);
  }

  /**
   * Relie une liste de SKU en gamme bidirectionnelle.
   *
   * @param list<string> $skus SKU des produits de la même gamme
   * @return void
   */
  private function linkRange(array $skus): void
  {
    $products = Product::query()->whereIn('sku', $skus)->get()->keyBy('sku');

    foreach ($skus as $sku) {
      $product = $products->get($sku);

      if (!$product) {
        continue;
      }

      $relatedIds = $products
        ->except($sku)
        ->pluck('id')
        ->all();

      $product->syncRangeCompanions($relatedIds);
    }
  }

  /**
   * Supprime les 200 produits de démonstration (et leurs images/variantes en cascade),
   * ainsi que les fichiers images placeholder associés sur le disque.
   *
   * @return void
   */
  private function deleteDemoCatalog(): void
  {
    $disk = Storage::disk('public');

    $demoImagePaths = ProductImage::query()
      ->whereHas('product', fn ($q) => $q->whereNotIn('sku', $this->realSkus()))
      ->pluck('path');

    Product::query()->whereNotIn('sku', $this->realSkus())->get()->each(function (Product $product) {
      $product->delete();
    });

    foreach ($demoImagePaths as $path) {
      if ($disk->exists($path)) {
        $disk->delete($path);
      }
    }
  }

  /**
   * SKUs du vrai catalogue (à ne jamais supprimer si le seeder est rejoué).
   *
   * @return list<string>
   */
  private function realSkus(): array
  {
    return [
      'CL-MIN-0001', 'CL-FES-0002', 'CL-FES-0003', 'CL-MIN-0004', 'CL-HUI-0005',
      'CL-MIN-0006', 'CL-COR-0007', 'CL-COR-0008', 'CL-GOM-0009', 'CL-FES-0010',
      'DZ-MIN-0011', 'DZ-FES-0012', 'YK-MIN-0013',
    ];
  }
}
