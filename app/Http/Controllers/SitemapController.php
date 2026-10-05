<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Response;

/**
 * Génère le sitemap XML (pages publiques + fiches produit actives).
 */
class SitemapController extends Controller
{
  /**
   * @return Response Réponse XML
   */
  public function __invoke(): Response
  {
    $urls = [
      ['loc' => route('home'), 'priority' => '1.0', 'changefreq' => 'daily'],
      ['loc' => route('shop.catalog'), 'priority' => '0.9', 'changefreq' => 'daily'],
      ['loc' => route('shop.about'), 'priority' => '0.5', 'changefreq' => 'monthly'],
      ['loc' => route('legal.show', 'cgv'), 'priority' => '0.3', 'changefreq' => 'yearly'],
      ['loc' => route('legal.show', 'confidentialite'), 'priority' => '0.3', 'changefreq' => 'yearly'],
      ['loc' => route('legal.show', 'retours'), 'priority' => '0.3', 'changefreq' => 'yearly'],
    ];

    Product::query()
      ->where('is_active', true)
      ->select(['slug', 'updated_at'])
      ->orderByDesc('updated_at')
      ->chunk(200, function ($products) use (&$urls): void {
        foreach ($products as $product) {
          $urls[] = [
            'loc' => route('products.show', $product),
            'lastmod' => $product->updated_at?->toAtomString(),
            'priority' => '0.7',
            'changefreq' => 'weekly',
          ];
        }
      });

    $xml = view('sitemap', ['urls' => $urls])->render();

    return response($xml, 200)->header('Content-Type', 'application/xml');
  }
}
