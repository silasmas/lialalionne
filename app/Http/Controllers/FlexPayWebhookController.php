<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur callback FlexPay (Mobile Money + carte).
 */
class FlexPayWebhookController extends Controller
{
  /**
   * Reçoit le callback FlexPay et confirme la commande si succès.
   *
   * @param Request $request Requête HTTP
   * @param PaymentService $paymentService Service paiement
   * @return JsonResponse Accusé de réception
   */
  public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
  {
    $paymentService->handleFlexPayCallback($request);

    return response()->json(['received' => true]);
  }
}
