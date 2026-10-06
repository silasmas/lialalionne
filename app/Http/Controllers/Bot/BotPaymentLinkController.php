<?php

namespace App\Http\Controllers\Bot;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\BotCommerceService;
use App\Services\PaymentService;
use App\Services\SiteSettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Lien de paiement par carte /payer/{token} envoyé par le bot WhatsApp.
 * Le Mobile Money, lui, se règle directement dans la conversation (push
 * FlexPay sur le téléphone de la cliente).
 */
class BotPaymentLinkController extends Controller
{
  /**
   * @param PaymentService $payments Paiements FlexPay / Stripe
   * @param SiteSettingsService $settings Moyens de paiement activés
   * @param BotCommerceService $bot Conversion de devise de la commande
   */
  public function __construct(
    private readonly PaymentService $payments,
    private readonly SiteSettingsService $settings,
    private readonly BotCommerceService $bot
  ) {
  }

  /**
   * Affiche le récapitulatif et le bouton de paiement par carte.
   *
   * @param string $token Jeton du lien
   * @return View Page de paiement
   */
  public function show(string $token): View
  {
    $order = $this->findOrder($token);

    if (!$order) {
      return view('bot.pay', ['order' => null, 'state' => 'invalid']);
    }

    return view('bot.pay', [
      'order' => $order,
      'state' => $this->state($order),
      'token' => $token,
      'cardEnabled' => $this->settings->isPaymentMethodEnabled(PaymentMethod::Stripe),
    ]);
  }

  /**
   * Redirige vers la passerelle de paiement par carte, dans la devise
   * choisie sur la page (CDF ou USD).
   *
   * @param Request $request Requête (currency optionnelle)
   * @param string $token Jeton du lien
   * @return RedirectResponse Passerelle carte
   */
  public function card(Request $request, string $token): RedirectResponse
  {
    $order = $this->findOrder($token);

    abort_if(!$order, 404);
    abort_if($this->state($order) !== 'pending', 410, 'Ce lien de paiement n\'est plus valable.');

    if (!$this->settings->isPaymentMethodEnabled(PaymentMethod::Stripe)) {
      throw ValidationException::withMessages(['payment' => 'Le paiement par carte n\'est pas disponible.']);
    }

    $currency = strtoupper((string) $request->input('currency'));

    if (in_array($currency, BotCommerceService::PAYMENT_CURRENCIES, true) && $currency !== $order->currency) {
      $order = $this->bot->switchCurrency($order, $currency);
    }

    if ($order->payment_method !== PaymentMethod::Stripe) {
      $order->update(['payment_method' => PaymentMethod::Stripe]);
      $order->payment?->update(['method' => PaymentMethod::Stripe]);
    }

    $result = $this->payments->initiate($order->fresh(['payment', 'items']));

    return redirect()->away($result['redirect_url']);
  }

  /**
   * @param Order $order Commande
   * @return string pending, paid, cancelled ou expired
   */
  private function state(Order $order): string
  {
    return match (true) {
      $order->status === OrderStatus::Cancelled => 'cancelled',
      $order->status !== OrderStatus::Pending => 'paid',
      $order->payment_token_expires_at && $order->payment_token_expires_at->isPast() => 'expired',
      default => 'pending',
    };
  }

  /**
   * @param string $token Jeton du lien
   * @return Order|null Commande WhatsApp liée au jeton
   */
  private function findOrder(string $token): ?Order
  {
    if (strlen($token) < 32) {
      return null;
    }

    return Order::query()
      ->with(['items', 'payment', 'user'])
      ->where('payment_token', $token)
      ->first();
  }
}
