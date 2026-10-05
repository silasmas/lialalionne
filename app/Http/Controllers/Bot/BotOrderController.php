<?php

namespace App\Http\Controllers\Bot;

use App\Http\Controllers\Controller;
use App\Services\BotCommerceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * API bot : clientes, devis, commandes, suivi et codes promo.
 */
class BotOrderController extends Controller
{
  /**
   * @param BotCommerceService $bot Service métier du bot
   */
  public function __construct(private readonly BotCommerceService $bot)
  {
  }

  /**
   * GET /api/bot/clientes/{phone}
   *
   * @param string $phone Numéro WhatsApp
   * @return JsonResponse Profil ou {known:false}
   */
  public function customer(string $phone): JsonResponse
  {
    $user = $this->bot->findCustomer($phone);

    if (!$user) {
      return response()->json(['known' => false]);
    }

    return response()->json(['known' => true, 'customer' => $this->bot->customerPayload($user)]);
  }

  /**
   * POST /api/bot/devis — récapitulatif sans création.
   *
   * @param Request $request Requête
   * @return JsonResponse Devis
   */
  public function quote(Request $request): JsonResponse
  {
    $data = $request->validate($this->itemRules() + [
      'phone' => ['nullable', 'string', 'max:30'],
      'fulfillment_type' => ['nullable', 'in:delivery,pickup'],
      'shipping_rate_id' => ['nullable', 'integer'],
      'coupon_code' => ['nullable', 'string', 'max:50'],
      'currency' => ['nullable', 'in:CDF,USD'],
    ]);

    return response()->json(['quote' => $this->bot->quote($data)]);
  }

  /**
   * POST /api/bot/commandes — crée la commande après le « Oui » de la cliente.
   *
   * @param Request $request Requête
   * @return JsonResponse Commande créée (201)
   */
  public function store(Request $request): JsonResponse
  {
    $data = $request->validate($this->itemRules() + [
      'phone' => ['required', 'string', 'max:30'],
      'name' => ['nullable', 'string', 'max:120'],
      'fulfillment_type' => ['required', 'in:delivery,pickup'],
      'shipping_rate_id' => ['nullable', 'integer'],
      'address' => ['nullable', 'string', 'max:255'],
      'commune' => ['nullable', 'string', 'max:120'],
      'city' => ['nullable', 'string', 'max:120'],
      'payment_method' => ['required', 'in:mobile_money,card,cod'],
      'coupon_code' => ['nullable', 'string', 'max:50'],
      'currency' => ['nullable', 'in:CDF,USD'],
      'notes' => ['nullable', 'string', 'max:500'],
    ]);

    $order = $this->bot->createOrder($data);

    return response()->json(['order' => $this->bot->orderPayload($order)], 201);
  }

  /**
   * GET /api/bot/commandes/{orderNumber}?phone=
   *
   * @param Request $request Requête
   * @param string $orderNumber Numéro de commande
   * @return JsonResponse Statut de la commande
   */
  public function show(Request $request, string $orderNumber): JsonResponse
  {
    $request->validate(['phone' => ['required', 'string', 'max:30']]);

    $order = $this->bot->findOrderForPhone($orderNumber, (string) $request->query('phone'));

    if (!$order) {
      return response()->json(['message' => 'Commande introuvable pour ce numéro.'], 404);
    }

    return response()->json(['order' => $this->bot->orderPayload($order)]);
  }

  /**
   * POST /api/bot/commandes/{orderNumber}/mobile-money — push FlexPay sur le téléphone.
   *
   * @param Request $request Requête
   * @param string $orderNumber Numéro de commande
   * @return JsonResponse Résultat de la demande
   */
  public function payMobileMoney(Request $request, string $orderNumber): JsonResponse
  {
    $data = $request->validate([
      'phone' => ['required', 'string', 'max:30'],
      'payer_phone' => ['nullable', 'string', 'max:30'],
      'operator' => ['nullable', 'in:mpesa,airtel,orange,afrimoney'],
    ]);

    $order = $this->ownedOrder($orderNumber, $data['phone']);

    if (!$order) {
      return response()->json(['message' => 'Commande introuvable pour ce numéro.'], 404);
    }

    return response()->json($this->bot->payMobileMoney($order, $data['payer_phone'] ?? null, $data['operator'] ?? null));
  }

  /**
   * POST /api/bot/commandes/{orderNumber}/verifier — revérifie le paiement.
   *
   * @param Request $request Requête
   * @param string $orderNumber Numéro de commande
   * @return JsonResponse Statut
   */
  public function verifyPayment(Request $request, string $orderNumber): JsonResponse
  {
    $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
    $order = $this->ownedOrder($orderNumber, $data['phone']);

    if (!$order) {
      return response()->json(['message' => 'Commande introuvable pour ce numéro.'], 404);
    }

    return response()->json($this->bot->verifyPayment($order));
  }

  /**
   * POST /api/bot/commandes/{orderNumber}/lien-carte — lien de paiement par carte.
   *
   * @param Request $request Requête
   * @param string $orderNumber Numéro de commande
   * @return JsonResponse URL à envoyer
   */
  public function cardLink(Request $request, string $orderNumber): JsonResponse
  {
    $data = $request->validate(['phone' => ['required', 'string', 'max:30']]);
    $order = $this->ownedOrder($orderNumber, $data['phone']);

    if (!$order) {
      return response()->json(['message' => 'Commande introuvable pour ce numéro.'], 404);
    }

    return response()->json(['card_payment_url' => $this->bot->cardPaymentUrl($order)]);
  }

  /**
   * @param string $orderNumber Numéro de commande
   * @param string $phone Numéro WhatsApp
   * @return \App\Models\Order|null Commande de cette cliente
   */
  private function ownedOrder(string $orderNumber, string $phone): ?\App\Models\Order
  {
    return $this->bot->findOrderForPhone($orderNumber, $phone);
  }

  /**
   * POST /api/bot/promo/verifier
   *
   * @param Request $request Requête
   * @return JsonResponse Validité et montant de la remise
   */
  public function coupon(Request $request): JsonResponse
  {
    $data = $request->validate($this->itemRules() + [
      'code' => ['required', 'string', 'max:50'],
      'phone' => ['nullable', 'string', 'max:30'],
    ]);

    return response()->json($this->bot->checkCoupon($data['code'], $data['items'], $data['phone'] ?? null));
  }

  /**
   * @return array<string, mixed> Règles communes des lignes produits
   */
  private function itemRules(): array
  {
    return [
      'items' => ['required', 'array', 'min:1', 'max:20'],
      'items.*.sku' => ['required', 'string', 'max:100'],
      'items.*.quantity' => ['nullable', 'integer', 'min:1', 'max:20'],
      'items.*.variant' => ['nullable', 'string', 'max:100'],
    ];
  }
}
