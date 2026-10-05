<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\BotCommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API bot : catalogue, fiches produits, routines et infos pratiques.
 */
class BotCatalogController extends Controller
{
  /**
   * @param BotCommerceService $bot Service métier du bot
   */
  public function __construct(private readonly BotCommerceService $bot)
  {
  }

  /**
   * GET /api/bot/catalogue?q=&categorie=
   *
   * @param Request $request Requête
   * @return JsonResponse Produits actifs
   */
  public function index(Request $request): JsonResponse
  {
    $products = $this->bot->catalogue(
      $request->string('q')->trim()->value() ?: null,
      $request->string('categorie')->trim()->value() ?: null
    );

    return response()->json(['count' => $products->count(), 'products' => $products->values()]);
  }

  /**
   * GET /api/bot/produits/{reference} (SKU ou slug)
   *
   * @param string $reference SKU ou slug
   * @return JsonResponse Fiche complète
   */
  public function show(string $reference): JsonResponse
  {
    $product = $this->bot->findProduct($reference);

    if (!$product) {
      return response()->json(['message' => 'Produit introuvable.'], 404);
    }

    return response()->json(['product' => $this->bot->productDetail($product)]);
  }

  /**
   * GET /api/bot/routines
   *
   * @return JsonResponse Gammes avec prix kit
   */
  public function routines(): JsonResponse
  {
    return response()->json(['routines' => $this->bot->routines()]);
  }

  /**
   * GET /api/bot/boutique
   *
   * @return JsonResponse Livraison, retrait, paiements, devises
   */
  public function shop(): JsonResponse
  {
    return response()->json($this->bot->shopInfo());
  }
}
