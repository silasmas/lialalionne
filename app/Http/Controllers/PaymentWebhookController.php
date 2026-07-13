<?php

namespace App\Http\Controllers;

use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Contrôleur webhook passerelle de paiement (Stripe).
 */
class PaymentWebhookController extends Controller
{
  /**
   * Reçoit et traite le webhook Stripe checkout.session.completed.
   *
   * @param Request $request Requête HTTP
   * @param PaymentService $paymentService Service paiement
   * @return JsonResponse Accusé de réception
   */
  public function __invoke(Request $request, PaymentService $paymentService): JsonResponse
  {
    $paymentService->handleWebhook($request);

    return response()->json(['received' => true]);
  }
}
