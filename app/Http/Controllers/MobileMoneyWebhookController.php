<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur webhook Mobile Money (Flutterwave).
 */
class MobileMoneyWebhookController extends Controller
{
  /**
   * Reçoit et traite le webhook passerelle Mobile Money.
   *
   * @param Request $request Requête HTTP
   * @param PaymentService $paymentService Service paiement
   * @return JsonResponse Accusé de réception
   */
  public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
  {
    $paymentService->handleMobileMoneyWebhook($request);

    return response()->json(['received' => true]);
  }
}
