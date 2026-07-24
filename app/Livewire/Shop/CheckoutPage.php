<?php

namespace App\Livewire\Shop;

use App\Enums\MobileMoneyOperator;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Livewire\Shop\Concerns\DispatchesShopToast;
use App\Models\Order;
use App\Models\User;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\CurrencyService;
use App\Services\FlexPayService;
use App\Services\MobileMoneyService;
use App\Services\OrderService;
use App\Services\PaymentService;
use App\Services\ShippingService;
use App\Services\SiteSettingsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

/**
 * Page checkout : adresse, livraison/retrait, devise et paiement.
 */
class CheckoutPage extends Component
{
  use DispatchesShopToast;

  public string $firstName = '';

  public string $lastName = '';

  public string $email = '';

  public string $phone = '';

  public string $addressLine1 = '';

  public string $addressLine2 = '';

  public string $city = '';

  public string $postalCode = '';

  public string $country = 'CD';

  public string $fulfillmentType = 'delivery';

  public ?int $shippingRateId = null;

  public string $notes = '';

  public string $paymentMethod = 'mobile_money';

  public string $mobileMoneyOperator = 'mpesa';

  public string $mobileMoneyPhone = '';

  public string $currency = 'CDF';

  public float $subtotalEur = 0;

  public float $subtotal = 0;

  public float $shippingAmount = 0;

  public float $discountEur = 0;

  public float $discountAmount = 0;

  public float $total = 0;

  public string $couponCode = '';

  public ?string $appliedCouponCode = null;

  public ?string $appliedCouponLabel = null;

  public string $checkoutPhase = 'form';

  public ?string $pendingOrderNumber = null;

  public bool $mobileMoneyPushSent = false;

  public ?string $mobileMoneyLiveStatus = null;

  public bool $isCheckingMobileMoneyPayment = false;

  public int $mobileMoneyAutoPollCount = 0;

  public bool $showMobileMoneyVerifyButton = false;

  /** Nombre de relances automatiques avant d'afficher le bouton manuel. */
  private const MOBILE_MONEY_MANUAL_VERIFY_AFTER = 5;

  /**
   * Retourne l'étape courante du suivi Mobile Money (1 à 4).
   *
   * @return int Numéro d'étape actif
   */
  public function getCurrentMobileMoneyStepProperty(): int
  {
    if ($this->isCheckingMobileMoneyPayment) {
      return 4;
    }

    if ($this->mobileMoneyPushSent) {
      return 3;
    }

    if ($this->checkoutPhase === 'mobile_money') {
      return 2;
    }

    return 1;
  }

  /**
   * Initialise le checkout et préremplit les champs client.
   *
   * @param CartService $cartService Service panier
   * @param ShippingService $shippingService Service livraison
   * @param SiteSettingsService $settings Paramètres boutique
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function mount(
    CartService $cartService,
    ShippingService $shippingService,
    SiteSettingsService $settings,
    CurrencyService $currencyService
  ): void {
    $cart = $cartService->getCartWithItems();
    $resumeOrder = request()->query('order') ?? session('checkout_pending_order');

    if ($resumeOrder && $this->tryResumeMobileMoneyCheckout((string) $resumeOrder)) {
      return;
    }

    if ($cart->items->isEmpty()) {
      $this->redirect(route('shop.cart'), navigate: true);

      return;
    }

    $enabledMethods = $settings->enabledPaymentMethods();
    $this->paymentMethod = ($enabledMethods[0] ?? PaymentMethod::MobileMoney)->value;
    $this->currency = $currencyService->selectedCurrency();

    $user = Auth::user();

    if ($user) {
      $nameParts = explode(' ', $user->name, 2);
      $this->firstName = $nameParts[0] ?? '';
      $this->lastName = $nameParts[1] ?? '';
      $this->email = $user->email;
      $this->phone = $user->phone ?? '';
      $this->applySavedDeliveryAddress($user);
    }

    $this->subtotalEur = $cart->subtotal();
    $this->recalculateTotals($shippingService, $currencyService);
  }

  /**
   * Reprend un paiement Mobile Money en attente sur le checkout.
   *
   * @param string $orderNumber Numéro de commande
   * @return bool True si la reprise a réussi
   */
  private function tryResumeMobileMoneyCheckout(string $orderNumber): bool
  {
    $order = Order::query()
      ->where('order_number', $orderNumber)
      ->with('payment')
      ->first();

    if (
      !$order
      || $order->status !== OrderStatus::Pending
      || $order->payment_method !== PaymentMethod::MobileMoney
    ) {
      session()->forget('checkout_pending_order');

      return false;
    }

    if ($order->user_id && Auth::id() !== $order->user_id) {
      return false;
    }

    $this->checkoutPhase = 'mobile_money';
    $this->pendingOrderNumber = $order->order_number;
    $this->mobileMoneyOperator = (string) ($order->payment?->metadata['mobile_money_operator'] ?? 'mpesa');
    $this->mobileMoneyPhone = (string) ($order->payment?->metadata['mobile_money_phone'] ?? '');
    $this->mobileMoneyPushSent = !empty($order->payment?->metadata['flexpay_order_number']);
    $this->currency = $order->currency;
    $this->total = (float) $order->total;
    $this->mobileMoneyAutoPollCount = 0;
    $this->showMobileMoneyVerifyButton = false;
    $this->mobileMoneyLiveStatus = $this->mobileMoneyPushSent
      ? 'Confirmez le paiement sur votre téléphone. Vérification automatique en cours…'
      : null;
    session(['checkout_pending_order' => $order->order_number]);

    return true;
  }

  /**
   * Finalise le paiement Mobile Money directement sur le checkout.
   *
   * @param Order $order Commande créée
   * @param PaymentService $paymentService Service paiement
   * @param FlexPayService $flexPayService Passerelle FlexPay
   * @param MobileMoneyOperator $operator Opérateur choisi
   * @param string $phone Numéro normalisé
   * @return mixed Redirection succès ou affichage suivi paiement
   */
  private function finalizeMobileMoneyPayment(
    Order $order,
    PaymentService $paymentService,
    FlexPayService $flexPayService,
    MobileMoneyOperator $operator,
    string $phone
  ) {
    $order->refresh()->load('payment');

    if (!$flexPayService->isConfigured()) {
      $paymentService->confirmMobileMoney($order, $phone, [
        'mobile_money_operator' => $operator->value,
        'confirmed_via' => 'simulated',
      ]);
      session()->forget('checkout_pending_order');

      return $this->redirect(
        route('checkout.success', ['session_id' => $order->payment?->fresh()->transaction_id]),
        navigate: true
      );
    }

    try {
      $result = $paymentService->requestFlexPayMobilePayment($order, $phone, $operator);
      $this->checkoutPhase = 'mobile_money';
      $this->pendingOrderNumber = $order->order_number;
      $this->mobileMoneyPushSent = true;
      $this->mobileMoneyAutoPollCount = 0;
      $this->showMobileMoneyVerifyButton = false;
      $this->mobileMoneyLiveStatus = 'Confirmez le paiement sur votre téléphone. Vérification automatique en cours…';
      session(['checkout_pending_order' => $order->order_number]);

      return null;
    } catch (\Throwable $exception) {
      Log::error('Mobile money push failed', ['message' => $exception->getMessage()]);
      $this->addError(
        'checkout',
        'Le paiement Mobile Money n\'a pas pu être lancé. ' . ($exception->getMessage() ?: 'Réessayez dans quelques instants.')
      );

      return null;
    }
  }

  /**
   * Relance automatique de la vérification FlexPay (wire:poll).
   *
   * @param PaymentService $paymentService Service paiement
   * @return void
   */
  public function pollMobileMoneyPayment(PaymentService $paymentService): void
  {
    if (
      $this->checkoutPhase !== 'mobile_money'
      || !$this->mobileMoneyPushSent
      || !$this->pendingOrderNumber
      || $this->isCheckingMobileMoneyPayment
    ) {
      return;
    }

    $this->attemptMobileMoneyVerification($paymentService, manual: false);
  }

  /**
   * Vérifie le statut FlexPay et redirige vers la page succès si payé.
   *
   * @param PaymentService $paymentService Service paiement
   * @return mixed Redirection ou message d'attente
   */
  public function checkMobileMoneyPayment(PaymentService $paymentService)
  {
    return $this->attemptMobileMoneyVerification($paymentService, manual: true);
  }

  /**
   * Tente de confirmer le paiement Mobile Money (auto ou manuel).
   *
   * @param PaymentService $paymentService Service paiement
   * @param bool $manual True si l'utilisateur a cliqué sur « Vérifier »
   * @return mixed Redirection succès ou null
   */
  private function attemptMobileMoneyVerification(PaymentService $paymentService, bool $manual = false)
  {
    if (!$this->pendingOrderNumber) {
      return null;
    }

    $this->isCheckingMobileMoneyPayment = true;
    $this->mobileMoneyLiveStatus = 'Vérification en cours…';

    try {
      $order = Order::query()
        ->where('order_number', $this->pendingOrderNumber)
        ->firstOrFail();

      $order = $paymentService->verifyAndConfirmFlexPay($order->fresh(['payment']));
      session()->forget('checkout_pending_order');

      return $this->redirect(
        route('checkout.success', ['session_id' => $order->payment?->transaction_id]),
        navigate: true
      );
    } catch (ValidationException $exception) {
      if (!$manual) {
        $this->mobileMoneyAutoPollCount++;

        if ($this->mobileMoneyAutoPollCount >= self::MOBILE_MONEY_MANUAL_VERIFY_AFTER) {
          $this->showMobileMoneyVerifyButton = true;
        }

        $this->mobileMoneyLiveStatus = $this->showMobileMoneyVerifyButton
          ? 'Toujours en attente ? Validez sur votre téléphone ou cliquez ci-dessous.'
          : 'En attente de confirmation sur votre téléphone…';
      } else {
        $this->mobileMoneyLiveStatus = collect($exception->errors())->flatten()->first()
          ?? 'Paiement non confirmé. Réessayez après validation sur votre téléphone.';
      }
    } catch (\Throwable $exception) {
      Log::error('Mobile money status check failed', ['message' => $exception->getMessage()]);

      if ($manual) {
        $this->mobileMoneyLiveStatus = 'Impossible de vérifier le paiement. Réessayez dans quelques instants.';
      } else {
        $this->mobileMoneyAutoPollCount++;

        if ($this->mobileMoneyAutoPollCount >= self::MOBILE_MONEY_MANUAL_VERIFY_AFTER) {
          $this->showMobileMoneyVerifyButton = true;
        }

        $this->mobileMoneyLiveStatus = 'Vérification automatique en cours…';
      }
    } finally {
      $this->isCheckingMobileMoneyPayment = false;
    }

    return null;
  }

  /**
   * Préremplit l'adresse de livraison depuis le profil client.
   *
   * @param User|null $user Utilisateur connecté
   * @return void
   */
  private function applySavedDeliveryAddress(?User $user): void
  {
    if ($user === null) {
      return;
    }

    if ($user->delivery_address_line_1) {
      $this->addressLine1 = $user->delivery_address_line_1;
    }

    if ($user->delivery_address_line_2) {
      $this->addressLine2 = $user->delivery_address_line_2;
    }

    if ($user->delivery_city) {
      $this->city = $user->delivery_city;
    }

    if ($user->delivery_postal_code) {
      $this->postalCode = $user->delivery_postal_code;
    }

    if ($user->delivery_country) {
      $this->country = $user->delivery_country;
    }
  }

  /**
   * Efface l'erreur de validation d'un champ dès qu'il est modifié.
   *
   * @param string $field Nom de la propriété Livewire
   * @return void
   */
  private function clearFieldValidation(string $field): void
  {
    $this->resetValidation($field);
  }

  public function updatedFirstName(): void
  {
    $this->clearFieldValidation('firstName');
  }

  public function updatedLastName(): void
  {
    $this->clearFieldValidation('lastName');
  }

  public function updatedEmail(): void
  {
    $this->clearFieldValidation('email');
  }

  public function updatedPhone(): void
  {
    $this->clearFieldValidation('phone');
  }

  public function updatedAddressLine1(): void
  {
    $this->clearFieldValidation('addressLine1');
  }

  public function updatedAddressLine2(): void
  {
    $this->clearFieldValidation('addressLine2');
  }

  public function updatedCity(): void
  {
    $this->clearFieldValidation('city');
  }

  public function updatedPostalCode(): void
  {
    $this->clearFieldValidation('postalCode');
  }

  public function updatedNotes(): void
  {
    $this->clearFieldValidation('notes');
  }

  public function updatedPaymentMethod(): void
  {
    $this->clearFieldValidation('paymentMethod');
  }

  public function updatedMobileMoneyOperator(): void
  {
    $this->clearFieldValidation('mobileMoneyOperator');
    $this->validateMobileMoneyPhoneField();
  }

  public function updatedMobileMoneyPhone(): void
  {
    $this->clearFieldValidation('mobileMoneyPhone');
    $this->validateMobileMoneyPhoneField();
  }

  /**
   * Valide le numéro Mobile Money en temps réel si le champ est renseigné.
   *
   * @return void
   */
  private function validateMobileMoneyPhoneField(): void
  {
    if ($this->paymentMethod !== PaymentMethod::MobileMoney->value) {
      return;
    }

    if (trim($this->mobileMoneyPhone) === '') {
      return;
    }

    try {
      $operator = MobileMoneyOperator::from($this->mobileMoneyOperator);
      app(MobileMoneyService::class)->validatePhoneForOperator($this->mobileMoneyPhone, $operator);
    } catch (ValidationException $exception) {
      foreach ($exception->errors() as $field => $messages) {
        foreach ($messages as $message) {
          $this->addError($field, $message);
        }
      }
    }
  }

  /**
   * Recalcule les montants quand le pays change.
   *
   * @param ShippingService $shippingService Service livraison
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function updatedCountry(ShippingService $shippingService, CurrencyService $currencyService): void
  {
    $this->clearFieldValidation('country');
    $this->shippingRateId = null;
    $this->recalculateTotals($shippingService, $currencyService);
  }

  /**
   * Recalcule quand le mode livraison/retrait change.
   *
   * @param ShippingService $shippingService Service livraison
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function updatedFulfillmentType(ShippingService $shippingService, CurrencyService $currencyService): void
  {
    $this->clearFieldValidation('fulfillmentType');

    if ($this->fulfillmentType === 'delivery') {
      $this->applySavedDeliveryAddress(Auth::user());
    }

    $this->recalculateTotals($shippingService, $currencyService);
  }

  /**
   * Recalcule quand le tarif de livraison change.
   *
   * @param ShippingService $shippingService Service livraison
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function updatedShippingRateId(ShippingService $shippingService, CurrencyService $currencyService): void
  {
    $this->clearFieldValidation('shippingRateId');
    $this->recalculateTotals($shippingService, $currencyService);
  }

  /**
   * Recalcule quand la devise change.
   *
   * @param CurrencyService $currencyService Service devises
   * @param ShippingService $shippingService Service livraison
   * @return void
   */
  public function updatedCurrency(CurrencyService $currencyService, ShippingService $shippingService): void
  {
    $this->clearFieldValidation('currency');
    $currencyService->setSelectedCurrency($this->currency);
    $this->recalculateTotals($shippingService, $currencyService);
  }

  /**
   * Applique un code promo saisi au checkout.
   *
   * @param CouponService $couponService Service codes promo
   * @param ShippingService $shippingService Service livraison
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function applyCoupon(
    CouponService $couponService,
    ShippingService $shippingService,
    CurrencyService $currencyService
  ): void {
    $this->resetValidation('couponCode');

    try {
      $coupon = $couponService->validateForCheckout(
        $this->couponCode,
        $this->subtotalEur,
        Auth::user()
      );

      $this->appliedCouponCode = $coupon->code;
      $this->appliedCouponLabel = $coupon->name;
      $this->couponCode = $coupon->code;
      $this->discountEur = $couponService->calculateDiscountEur($coupon, $this->subtotalEur);
      $this->recalculateTotals($shippingService, $currencyService);
      $this->dispatchShopToast('Code promo « ' . $coupon->code . ' » appliqué.', 'success');
    } catch (ValidationException $exception) {
      $this->appliedCouponCode = null;
      $this->appliedCouponLabel = null;
      $this->discountEur = 0;
      $this->recalculateTotals($shippingService, $currencyService);

      foreach ($exception->errors() as $field => $messages) {
        foreach ($messages as $message) {
          $this->addError($field, $message);
        }
      }
    }
  }

  /**
   * Retire le code promo appliqué.
   *
   * @param ShippingService $shippingService Service livraison
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  public function removeCoupon(ShippingService $shippingService, CurrencyService $currencyService): void
  {
    $this->couponCode = '';
    $this->appliedCouponCode = null;
    $this->appliedCouponLabel = null;
    $this->discountEur = 0;
    $this->resetValidation('couponCode');
    $this->recalculateTotals($shippingService, $currencyService);
  }

  /**
   * Met à jour sous-total, livraison, remise et total dans la devise choisie.
   *
   * @param ShippingService $shippingService Service livraison
   * @param CurrencyService $currencyService Service devises
   * @return void
   */
  private function recalculateTotals(ShippingService $shippingService, CurrencyService $currencyService): void
  {
    if ($this->appliedCouponCode) {
      try {
        $coupon = app(CouponService::class)->validateForCheckout(
          $this->appliedCouponCode,
          $this->subtotalEur,
          Auth::user()
        );
        $this->discountEur = app(CouponService::class)->calculateDiscountEur($coupon, $this->subtotalEur);
        $this->appliedCouponLabel = $coupon->name;
      } catch (ValidationException) {
        $this->appliedCouponCode = null;
        $this->appliedCouponLabel = null;
        $this->discountEur = 0;
        $this->couponCode = '';
      }
    } else {
      $this->discountEur = 0;
    }

    $this->subtotal = $currencyService->convertFromEur($this->subtotalEur, $this->currency);
    $this->discountAmount = $currencyService->convertFromEur($this->discountEur, $this->currency);

    if ($this->fulfillmentType === 'pickup') {
      $this->shippingAmount = 0;
      $this->total = max(0, $this->subtotal - $this->discountAmount);

      return;
    }

    $rates = $shippingService->getAvailableRates($this->subtotalEur, $this->country);

    if ($this->shippingRateId && !$rates->contains('id', $this->shippingRateId)) {
      $this->shippingRateId = null;
    }

    if (!$this->shippingRateId && $rates->isNotEmpty()) {
      $this->shippingRateId = $rates->first()->id;
    }

    $shippingEur = $shippingService->calculate($this->subtotalEur, $this->country, $this->shippingRateId);
    $this->shippingAmount = $currencyService->convertFromEur($shippingEur, $this->currency);
    $this->total = max(0, $this->subtotal + $this->shippingAmount - $this->discountAmount);
  }

  /**
   * Valide le formulaire, crée la commande et redirige vers le paiement.
   *
   * @param CartService $cartService Service panier
   * @param OrderService $orderService Service commandes
   * @param PaymentService $paymentService Service paiement
   * @param ShippingService $shippingService Service livraison
   * @param SiteSettingsService $settings Paramètres boutique
   * @param CurrencyService $currencyService Service devises
   * @param FlexPayService $flexPayService Passerelle FlexPay
   * @return mixed Redirection vers paiement ou page succès
   */
  public function placeOrder(
    CartService $cartService,
    OrderService $orderService,
    PaymentService $paymentService,
    ShippingService $shippingService,
    SiteSettingsService $settings,
    CurrencyService $currencyService,
    FlexPayService $flexPayService
  ) {
    $enabledValues = collect($settings->enabledPaymentMethods())->map->value->all();
    $currencyValues = implode(',', $currencyService->availableCurrencies());

    $rules = [
      'firstName' => ['required', 'string', 'max:255'],
      'lastName' => ['required', 'string', 'max:255'],
      'email' => ['required', 'email', 'max:255'],
      'phone' => ['required', 'string', 'max:20'],
      'fulfillmentType' => ['required', 'in:delivery,pickup'],
      'notes' => ['nullable', 'string', 'max:1000'],
      'paymentMethod' => ['required', 'in:' . implode(',', $enabledValues)],
      'currency' => ['required', 'in:' . $currencyValues],
    ];

    if ($this->fulfillmentType === 'delivery') {
      $rules['addressLine1'] = ['required', 'string', 'max:255'];
      $rules['addressLine2'] = ['nullable', 'string', 'max:255'];
      $rules['city'] = ['required', 'string', 'max:255'];
      $rules['postalCode'] = ['required', 'string', 'max:20'];
      $rules['country'] = ['required', 'string', 'size:2'];
      $rules['shippingRateId'] = ['required', 'integer', 'exists:shipping_rates,id'];
    } else {
      $rules['addressLine1'] = ['nullable', 'string', 'max:255'];
      $rules['city'] = ['nullable', 'string', 'max:255'];
      $rules['postalCode'] = ['nullable', 'string', 'max:20'];
    }

    if ($this->paymentMethod === PaymentMethod::MobileMoney->value) {
      $rules['mobileMoneyOperator'] = ['required', 'in:' . implode(',', array_map(fn (MobileMoneyOperator $op) => $op->value, MobileMoneyOperator::all()))];
      $rules['mobileMoneyPhone'] = ['required', 'string', 'min:9', 'max:20'];
    }

    $messages = [
      'firstName.required' => 'Le prénom est obligatoire.',
      'lastName.required' => 'Le nom est obligatoire.',
      'email.required' => 'L\'adresse e-mail est obligatoire.',
      'email.email' => 'L\'adresse e-mail n\'est pas valide.',
      'phone.required' => 'Le numéro de téléphone est obligatoire.',
      'addressLine1.required' => 'L\'adresse est obligatoire.',
      'city.required' => 'La ville est obligatoire.',
      'postalCode.required' => 'Le code postal est obligatoire.',
      'country.required' => 'Le pays est obligatoire.',
      'shippingRateId.required' => 'Veuillez choisir un mode de livraison.',
      'shippingRateId.exists' => 'Le mode de livraison sélectionné est invalide.',
      'paymentMethod.required' => 'Veuillez choisir un mode de paiement.',
      'paymentMethod.in' => 'Le mode de paiement sélectionné est invalide.',
      'mobileMoneyOperator.required' => 'Veuillez choisir un opérateur Mobile Money.',
      'mobileMoneyOperator.in' => 'L\'opérateur Mobile Money sélectionné est invalide.',
      'mobileMoneyPhone.required' => 'Le numéro Mobile Money est obligatoire.',
      'mobileMoneyPhone.min' => 'Le numéro Mobile Money doit contenir au moins :min caractères.',
      'currency.required' => 'Veuillez choisir une devise.',
      'currency.in' => 'La devise sélectionnée est invalide.',
      'fulfillmentType.required' => 'Veuillez choisir un mode de réception.',
      'fulfillmentType.in' => 'Le mode de réception sélectionné est invalide.',
      'notes.max' => 'Les notes ne peuvent pas dépasser :max caractères.',
    ];

    try {
      $this->validate($rules, $messages, [
        'firstName' => 'prénom',
        'lastName' => 'nom',
        'email' => 'e-mail',
        'phone' => 'téléphone',
        'addressLine1' => 'adresse',
        'city' => 'ville',
        'postalCode' => 'code postal',
        'country' => 'pays',
        'shippingRateId' => 'mode de livraison',
        'paymentMethod' => 'mode de paiement',
        'mobileMoneyOperator' => 'opérateur Mobile Money',
        'mobileMoneyPhone' => 'numéro Mobile Money',
        'fulfillmentType' => 'mode de réception',
        'currency' => 'devise',
      ]);
    } catch (ValidationException $exception) {
      $this->dispatchShopToast('Veuillez corriger les champs indiqués ci-dessous.', 'error');
      throw $exception;
    }

    if ($this->fulfillmentType === 'delivery') {
      $rates = $shippingService->getAvailableRates($this->subtotalEur, $this->country);

      if ($rates->isEmpty()) {
        $this->addError('checkout', 'Aucune option de livraison disponible pour ce pays. Choisissez le retrait en boutique ou contactez-nous.');

        return null;
      }
    }

    $paymentMethod = PaymentMethod::from($this->paymentMethod);
    $mobileMoneyPhone = null;
    $mobileMoneyOperator = null;
    $mobileMoneyOperatorEnum = null;

    if ($paymentMethod === PaymentMethod::MobileMoney) {
      try {
        $mobileMoneyOperatorEnum = MobileMoneyOperator::from($this->mobileMoneyOperator);
        $mobileMoneyPhone = app(MobileMoneyService::class)->validatePhoneForOperator(
          $this->mobileMoneyPhone,
          $mobileMoneyOperatorEnum
        );
        $mobileMoneyOperator = $mobileMoneyOperatorEnum->value;
      } catch (ValidationException $exception) {
        foreach ($exception->errors() as $field => $messages) {
          foreach ($messages as $message) {
            $this->addError($field, $message);
          }
        }

        return null;
      }
    }

    $this->recalculateTotals($shippingService, $currencyService);

    if ($this->fulfillmentType === 'pickup') {
      $this->addressLine1 = $settings->get('pickup_store_name', 'Boutique Lialalionne');
      $this->addressLine2 = $settings->get('pickup_store_address', 'Retrait en boutique');
      $this->city = 'Kinshasa';
      $this->postalCode = '00000';
      $this->country = 'CD';
    }

    try {
      $cart = $cartService->getCartWithItems();
      $shippingEur = $this->fulfillmentType === 'pickup'
        ? 0
        : $shippingService->calculate($this->subtotalEur, $this->country, $this->shippingRateId);

      $order = $orderService->createFromCheckout($cart, [
        'user_id' => Auth::id(),
        'first_name' => $this->firstName,
        'last_name' => $this->lastName,
        'phone' => $this->phone,
        'address_line_1' => $this->addressLine1,
        'address_line_2' => $this->addressLine2,
        'city' => $this->city,
        'postal_code' => $this->postalCode,
        'country' => $this->country,
        'shipping_amount' => $shippingEur,
        'shipping_rate_id' => $this->fulfillmentType === 'pickup' ? null : $this->shippingRateId,
        'fulfillment_type' => $this->fulfillmentType,
        'payment_method' => $paymentMethod,
        'currency' => $this->currency,
        'customer_email' => $this->email,
        'notes' => $this->notes ?: null,
        'coupon_code' => $this->appliedCouponCode,
        'mobile_money_operator' => $mobileMoneyOperator,
        'mobile_money_phone' => $mobileMoneyPhone,
      ]);

      $result = $paymentService->initiate($order);

      if ($paymentMethod === PaymentMethod::MobileMoney && $mobileMoneyOperatorEnum) {
        return $this->finalizeMobileMoneyPayment(
          $order,
          $paymentService,
          $flexPayService,
          $mobileMoneyOperatorEnum,
          $mobileMoneyPhone
        );
      }

      if ($result['simulated']) {
        return $this->redirect($result['redirect_url'], navigate: true);
      }

      return $this->redirect($result['redirect_url'], navigate: true);
    } catch (ValidationException $exception) {
      $errors = $exception->errors();
      $firstError = collect($errors)->flatten()->first() ?? 'Impossible de passer commande.';

      foreach ($errors as $field => $messages) {
        foreach ($messages as $message) {
          $this->addError($field, $message);
        }
      }

      if (!$this->getErrorBag()->has('mobileMoneyPhone') && !$this->getErrorBag()->has('checkout')) {
        $this->addError('checkout', $firstError);
      }
    } catch (\Throwable $exception) {
      Log::error('Checkout payment failed', [
        'message' => $exception->getMessage(),
        'payment_method' => $this->paymentMethod,
      ]);

      $this->addError(
        'checkout',
        'Le paiement n\'a pas pu être lancé. ' . ($exception->getMessage() ?: 'Vérifiez vos informations et réessayez.')
      );
    }
  }

  /**
   * Rendu de la page checkout.
   *
   * @param ShippingService $shippingService Service livraison
   * @param SiteSettingsService $settings Paramètres boutique
   * @param CurrencyService $currencyService Service devises
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(
    ShippingService $shippingService,
    SiteSettingsService $settings,
    CurrencyService $currencyService,
    CartService $cartService,
    FlexPayService $flexPayService
  ) {
    $shippingRates = $this->fulfillmentType === 'pickup'
      ? collect()
      : $shippingService->getAvailableRates($this->subtotalEur, $this->country);

    $cart = $cartService->getCartWithItems();

    $pendingOrder = null;

    if ($this->checkoutPhase === 'mobile_money' && $this->pendingOrderNumber) {
      $pendingOrder = Order::query()
        ->where('order_number', $this->pendingOrderNumber)
        ->with(['payment', 'items'])
        ->first();
    }

    $operator = MobileMoneyOperator::tryFrom($this->mobileMoneyOperator);

    return view('livewire.shop.checkout-page', [
      'shippingRates' => $shippingRates,
      'paymentMethods' => $settings->enabledPaymentMethods(),
      'pickupEnabled' => $settings->isPickupEnabled(),
      'pickupStoreName' => $settings->get('pickup_store_name'),
      'pickupStoreAddress' => $settings->get('pickup_store_address'),
      'currencyMode' => $settings->currencyMode(),
      'availableCurrencies' => $currencyService->availableCurrencies(),
      'currencyService' => $currencyService,
      'cartItems' => $cart->items,
      'pendingOrder' => $pendingOrder,
      'livePaymentEnabled' => $flexPayService->isConfigured(),
      'mobileMoneyOperatorLabel' => $operator?->label() ?? 'Mobile Money',
    ])->layout('layouts.shopwise', [
      'title' => 'Checkout — Lialalionne',
    ]);
  }
}
