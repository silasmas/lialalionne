<?php

namespace App\Services;

use App\Enums\MobileMoneyOperator;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Logique métier de l'API du bot WhatsApp : catalogue, routines, clientes
 * reconnues par leur numéro, devis et création de commandes.
 *
 * Les prix sont stockés dans la devise de base du site (« EUR » dans le code)
 * et toujours renvoyés convertis en CDF et USD via CurrencyService, pour que
 * le bot n'invente jamais un prix.
 */
class BotCommerceService
{
  /**
   * Devises acceptées par FlexPay pour le paiement.
   *
   * @var list<string>
   */
  public const PAYMENT_CURRENCIES = ['CDF', 'USD'];

  /**
   * @param CurrencyService $currency Conversion des prix
   * @param ShippingService $shipping Tarifs de livraison
   * @param CouponService $coupons Codes promo
   * @param CartService $carts Panier temporaire (contrôle de stock)
   * @param OrderService $orders Création des commandes
   * @param PaymentService $payments Paiement à la livraison
   * @param LoyaltyService $loyalty Points fidélité
   * @param SiteSettingsService $settings Paramètres boutique
   * @param MobileMoneyService $mobileMoney Normalisation des numéros
   */
  public function __construct(
    private readonly CurrencyService $currency,
    private readonly ShippingService $shipping,
    private readonly CouponService $coupons,
    private readonly CartService $carts,
    private readonly OrderService $orders,
    private readonly PaymentService $payments,
    private readonly LoyaltyService $loyalty,
    private readonly SiteSettingsService $settings,
    private readonly MobileMoneyService $mobileMoney
  ) {
  }

  /* ------------------------------------------------------------------ */
  /* Catalogue                                                           */
  /* ------------------------------------------------------------------ */

  /**
   * Liste les produits actifs, filtrables par texte et catégorie.
   *
   * @param string|null $search Texte libre (nom, description courte, SKU)
   * @param string|null $category Slug de catégorie
   * @return Collection<int, array<string, mixed>> Produits au format bot
   */
  public function catalogue(?string $search = null, ?string $category = null): Collection
  {
    $query = Product::query()
      ->active()
      ->with(['category', 'images', 'variants'])
      ->orderByDesc('is_featured')
      ->orderBy('name');

    if ($category) {
      $query->whereHas('category', fn ($q) => $q->where('slug', $category));
    }

    if ($search) {
      $term = '%' . trim($search) . '%';
      $query->where(function ($q) use ($term) {
        $q->where('name', 'like', $term)
          ->orWhere('short_description', 'like', $term)
          ->orWhere('sku', 'like', $term);
      });
    }

    return $query->get()->map(fn (Product $product) => $this->productSummary($product));
  }

  /**
   * Retrouve un produit actif par SKU ou slug.
   *
   * @param string $reference SKU ou slug
   * @return Product|null Produit trouvé
   */
  public function findProduct(string $reference): ?Product
  {
    return Product::query()
      ->active()
      ->with(['category', 'images', 'variants'])
      ->where(fn ($q) => $q->where('sku', $reference)->orWhere('slug', $reference))
      ->first();
  }

  /**
   * Fiche résumée d'un produit (liste, recherche).
   *
   * @param Product $product Produit
   * @return array<string, mixed> Données bot
   */
  public function productSummary(Product $product): array
  {
    return [
      'sku' => $product->sku,
      'slug' => $product->slug,
      'name' => $product->name,
      'category' => $product->category?->name,
      'category_slug' => $product->category?->slug,
      'short_description' => $product->short_description,
      'price' => $this->money((float) $product->price),
      'compare_at_price' => $product->hasDiscount() ? $this->money((float) $product->compare_at_price) : null,
      'in_stock' => $product->isInStock(),
      'is_new' => (bool) $product->is_new,
      'is_featured' => (bool) $product->is_featured,
      'variants' => $product->variants
        ->where('is_active', true)
        ->values()
        ->map(fn (ProductVariant $variant) => [
          'sku' => $variant->sku,
          'name' => $variant->name,
          'price' => $this->money((float) $variant->price),
          'in_stock' => !$product->track_stock || (int) $variant->stock > 0,
        ])
        ->all(),
      'image_url' => $this->absoluteUrl($product->primaryImageUrl()),
      'url' => route('products.show', $product),
    ];
  }

  /**
   * Fiche complète d'un produit (description, ingrédients, mode d'emploi, gamme).
   *
   * @param Product $product Produit
   * @return array<string, mixed> Données bot
   */
  public function productDetail(Product $product): array
  {
    return array_merge($this->productSummary($product), [
      'description' => $product->description,
      'ingredients' => $product->ingredients,
      'usage_tips' => $product->usage_tips,
      'images' => $product->images
        ->map(fn ($image) => $this->absoluteUrl($image->url))
        ->filter()
        ->values()
        ->all(),
      'routine_companions' => $product->rangeCompanions()
        ->map(fn (Product $companion) => [
          'sku' => $companion->sku,
          'name' => $companion->name,
          'price' => $this->money((float) $companion->price),
        ])
        ->values()
        ->all(),
    ]);
  }

  /**
   * Routines = gammes de produits reliés entre eux dans le back-office
   * (« produits de la même gamme »), avec le prix séparé et le prix kit.
   *
   * @return Collection<int, array<string, mixed>> Routines
   */
  public function routines(): Collection
  {
    $products = Product::query()->active()->with(['images', 'variants'])->get()->keyBy('id');

    $links = DB::table('product_related')
      ->whereIn('product_id', $products->keys())
      ->whereIn('related_product_id', $products->keys())
      ->get(['product_id', 'related_product_id']);

    $neighbours = [];
    foreach ($links as $link) {
      $neighbours[$link->product_id][] = $link->related_product_id;
      $neighbours[$link->related_product_id][] = $link->product_id;
    }

    $seen = [];
    $groups = [];

    foreach (array_keys($neighbours) as $start) {
      if (isset($seen[$start])) {
        continue;
      }

      $stack = [$start];
      $group = [];

      while ($stack) {
        $id = array_pop($stack);
        if (isset($seen[$id])) {
          continue;
        }
        $seen[$id] = true;
        $group[] = $id;
        foreach ($neighbours[$id] ?? [] as $next) {
          if (!isset($seen[$next])) {
            $stack[] = $next;
          }
        }
      }

      if (count($group) >= 2) {
        sort($group);
        $groups[] = $group;
      }
    }

    $percent = $this->kitDiscountPercent();

    return collect($groups)->map(function (array $ids) use ($products, $percent) {
      $members = collect($ids)->map(fn ($id) => $products->get($id))->filter()->values();
      $totalEur = (float) $members->sum(fn (Product $p) => (float) $p->price);
      $kitEur = round($totalEur * (1 - $percent / 100), 2);

      return [
        'id' => 'routine-' . $members->first()->sku,
        'products' => $members->map(fn (Product $p) => [
          'sku' => $p->sku,
          'name' => $p->name,
          'price' => $this->money((float) $p->price),
          'in_stock' => $p->isInStock(),
        ])->all(),
        'price_separately' => $this->money($totalEur),
        'kit_discount_percent' => $percent,
        'kit_price' => $this->money($kitEur),
      ];
    })->values();
  }

  /**
   * Infos pratiques : livraison, retrait, moyens de paiement, devises.
   *
   * @return array<string, mixed> Infos boutique
   */
  public function shopInfo(): array
  {
    return [
      'currencies' => $this->currency->availableCurrencies(),
      'primary_currency' => $this->currency->primaryCurrency(),
      'shipping_rates' => $this->shipping->getAvailableRates(0, $this->country())
        ->map(fn ($rate) => [
          'id' => $rate->id,
          'name' => $rate->name,
          'price' => $this->money((float) $rate->price),
          'min_order' => (float) $rate->min_order_amount > 0 ? $this->money((float) $rate->min_order_amount) : null,
          'days_min' => $rate->estimated_days_min,
          'days_max' => $rate->estimated_days_max,
        ])
        ->values()
        ->all(),
      'pickup' => [
        'enabled' => $this->settings->isPickupEnabled(),
        'store_name' => $this->settings->get('pickup_store_name'),
        'address' => $this->settings->get('pickup_store_address'),
      ],
      'payment_methods' => collect($this->settings->enabledPaymentMethods())
        ->map(fn (PaymentMethod $method) => [
          'code' => $this->paymentCode($method),
          'label' => $method->label(),
        ])
        ->values()
        ->all(),
      'kit_discount_percent' => $this->kitDiscountPercent(),
    ];
  }

  /* ------------------------------------------------------------------ */
  /* Clientes                                                            */
  /* ------------------------------------------------------------------ */

  /**
   * Normalise un numéro au format 243XXXXXXXXX.
   *
   * @param string $phone Numéro brut (WhatsApp, saisie)
   * @return string Numéro normalisé
   */
  public function normalizePhone(string $phone): string
  {
    return $this->mobileMoney->normalizePhone($phone);
  }

  /**
   * Retrouve une cliente par son numéro, quel que soit le format enregistré.
   *
   * @param string $phone Numéro WhatsApp
   * @return User|null Cliente trouvée
   */
  public function findCustomer(string $phone): ?User
  {
    $normalized = $this->normalizePhone($phone);

    if (strlen($normalized) < 9) {
      return null;
    }

    $lastDigits = substr($normalized, -9);

    return User::query()
      ->where('phone', 'like', '%' . substr($lastDigits, -4))
      ->get()
      ->first(fn (User $user) => $this->normalizePhone((string) $user->phone) === $normalized);
  }

  /**
   * Retrouve ou crée la cliente (compte sans mot de passe utilisable,
   * connexion ultérieure par code SMS comme sur le site).
   *
   * @param string $phone Numéro WhatsApp
   * @param string|null $name Nom complet
   * @return User Cliente
   */
  public function findOrCreateCustomer(string $phone, ?string $name = null): User
  {
    $existing = $this->findCustomer($phone);

    if ($existing) {
      if ($name && trim((string) $existing->name) === '') {
        $existing->update(['name' => $name]);
      }

      return $existing;
    }

    $normalized = $this->normalizePhone($phone);

    if (strlen($normalized) !== 12) {
      throw ValidationException::withMessages([
        'phone' => 'Numéro de téléphone invalide (format attendu : 243XXXXXXXXX).',
      ]);
    }

    if (!$name || trim($name) === '') {
      throw ValidationException::withMessages([
        'name' => 'Le nom de la cliente est requis pour une première commande.',
      ]);
    }

    return User::query()->create([
      'name' => trim($name),
      'email' => $normalized . '@otp.lialalionne.local',
      'phone' => '+' . $normalized,
      'password' => Str::random(40),
      'is_admin' => false,
    ]);
  }

  /**
   * Profil cliente pour le bot : nom, points, dernières commandes.
   *
   * @param User $user Cliente
   * @return array<string, mixed> Profil
   */
  public function customerPayload(User $user): array
  {
    $points = $this->loyalty->balance($user);

    return [
      'name' => $user->name,
      'phone' => '+' . $this->normalizePhone((string) $user->phone),
      'loyalty_points' => $points,
      'loyalty_value' => $this->money($this->loyalty->eurValueOfPoints($points)),
      'orders_count' => $user->orders()->count(),
      'recent_orders' => $user->orders()
        ->with(['items', 'payment'])
        ->latest()
        ->limit(5)
        ->get()
        ->map(fn (Order $order) => $this->orderPayload($order))
        ->all(),
    ];
  }

  /* ------------------------------------------------------------------ */
  /* Devis et commandes                                                  */
  /* ------------------------------------------------------------------ */

  /**
   * Calcule le montant d'une commande sans l'enregistrer (récapitulatif
   * envoyé à la cliente avant son « Oui »).
   *
   * @param array<string, mixed> $data Données validées
   * @return array<string, mixed> Devis
   */
  public function quote(array $data): array
  {
    $currency = $this->resolveCurrency($data['currency'] ?? null);
    $lines = $this->resolveLines($data['items']);
    $user = isset($data['phone']) ? $this->findCustomer((string) $data['phone']) : null;

    $subtotalEur = (float) $lines->sum(fn (array $line) => $line['unit_price_eur'] * $line['quantity']);
    $fulfillment = $this->resolveFulfillment($data['fulfillment_type'] ?? 'delivery');
    $shippingEur = $fulfillment === 'pickup'
      ? 0.0
      : $this->shipping->calculate($subtotalEur, $this->country(), isset($data['shipping_rate_id']) ? (int) $data['shipping_rate_id'] : null);

    [$discountEur, $discountLabel] = $this->discount($lines, $subtotalEur, $data['coupon_code'] ?? null, $user);

    $totalEur = max(0, $subtotalEur + $shippingEur - $discountEur);

    return [
      'currency' => $currency,
      'items' => $lines->map(fn (array $line) => [
        'sku' => $line['sku'],
        'name' => $line['name'],
        'quantity' => $line['quantity'],
        'unit_price' => $this->amount($line['unit_price_eur'], $currency),
        'line_total' => $this->amount($line['unit_price_eur'] * $line['quantity'], $currency),
      ])->values()->all(),
      'fulfillment_type' => $fulfillment,
      'subtotal' => $this->amount($subtotalEur, $currency),
      'shipping' => $this->amount($shippingEur, $currency),
      'discount' => $this->amount($discountEur, $currency),
      'discount_label' => $discountLabel,
      'total' => $this->amount($totalEur, $currency),
      'total_other_currency' => $this->otherCurrencyAmount($totalEur, $currency),
    ];
  }

  /**
   * Crée la commande WhatsApp (après confirmation explicite de la cliente).
   *
   * @param array<string, mixed> $data Données validées
   * @return Order Commande créée
   */
  public function createOrder(array $data): Order
  {
    $user = $this->findOrCreateCustomer((string) $data['phone'], $data['name'] ?? null);
    $currency = $this->resolveCurrency($data['currency'] ?? null);
    $fulfillment = $this->resolveFulfillment($data['fulfillment_type'] ?? 'delivery');
    $method = $this->resolvePaymentMethod((string) ($data['payment_method'] ?? 'mobile_money'));
    $lines = $this->resolveLines($data['items']);

    if ($fulfillment === 'delivery' && empty($data['address'])) {
      throw ValidationException::withMessages([
        'address' => 'L\'adresse de livraison est requise (ou choisir le retrait en boutique).',
      ]);
    }

    $cart = Cart::query()->create(['session_id' => 'bot-' . Str::uuid()]);

    try {
      foreach ($lines as $line) {
        $this->carts->addItem($cart, $line['product'], $line['quantity'], $line['variant']);
      }

      $subtotalEur = $cart->fresh('items')->subtotal();
      $shippingRateId = isset($data['shipping_rate_id']) ? (int) $data['shipping_rate_id'] : null;
      $shippingEur = $fulfillment === 'pickup'
        ? 0.0
        : $this->shipping->calculate($subtotalEur, $this->country(), $shippingRateId);

      $couponCode = !empty($data['coupon_code']) ? (string) $data['coupon_code'] : null;
      $kitDiscountEur = $couponCode ? 0.0 : $this->kitDiscountEur($lines);

      [$firstName, $lastName] = $this->splitName((string) ($data['name'] ?? $user->name));
      $normalizedPhone = '+' . $this->normalizePhone((string) $data['phone']);

      $orderData = [
        'user_id' => $user->id,
        'currency' => $currency,
        'fulfillment_type' => $fulfillment,
        'shipping_amount' => $shippingEur,
        'shipping_rate_id' => $shippingRateId,
        'payment_method' => $method,
        'coupon_code' => $couponCode,
        'customer_email' => $user->email,
        'notes' => $this->notes($data),
        'first_name' => $firstName,
        'last_name' => $lastName,
        'phone' => $normalizedPhone,
        'address_line_1' => $fulfillment === 'pickup'
          ? (string) ($this->settings->get('pickup_store_name') ?? 'Retrait en boutique')
          : (string) $data['address'],
        'address_line_2' => $data['commune'] ?? null,
        'city' => (string) ($data['city'] ?? 'Kinshasa'),
        'postal_code' => '',
        'country' => $this->country(),
        'mobile_money_phone' => $method === PaymentMethod::MobileMoney ? $normalizedPhone : null,
      ];

      if ($kitDiscountEur > 0) {
        $orderData['discount_amount'] = $kitDiscountEur;
      }

      $order = $this->orders->createFromCheckout($cart->fresh('items'), $orderData);
    } finally {
      $cart->delete();
    }

    $order->update([
      'source' => 'whatsapp',
      'payment_token' => $method === PaymentMethod::Cod ? null : Str::random(48),
      'payment_token_expires_at' => $method === PaymentMethod::Cod
        ? null
        : now()->addHours(max(1, (int) config('bot.payment_link_ttl_hours', 72))),
    ]);

    if ($method === PaymentMethod::Cod) {
      $this->payments->initiate($order->fresh(['payment']));
    }

    return $order->fresh(['items', 'payment', 'addresses', 'user']);
  }

  /**
   * Retrouve une commande appartenant à ce numéro.
   *
   * @param string $orderNumber Numéro de commande (LL-XXXXXXXX)
   * @param string $phone Numéro WhatsApp de la cliente
   * @return Order|null Commande si le numéro correspond
   */
  public function findOrderForPhone(string $orderNumber, string $phone): ?Order
  {
    $order = Order::query()
      ->with(['items', 'payment', 'addresses', 'user'])
      ->where('order_number', strtoupper(trim($orderNumber)))
      ->first();

    if (!$order) {
      return null;
    }

    $normalized = $this->normalizePhone($phone);
    $phones = collect([$order->user?->phone])
      ->merge($order->addresses->pluck('phone'))
      ->filter()
      ->map(fn ($p) => $this->normalizePhone((string) $p));

    return $phones->contains($normalized) ? $order : null;
  }

  /**
   * Commande au format bot (statut lisible, montants, lien de paiement).
   *
   * @param Order $order Commande
   * @return array<string, mixed> Données bot
   */
  public function orderPayload(Order $order): array
  {
    $order->loadMissing(['items', 'payment']);
    $currency = $order->currency ?: $this->currency->primaryCurrency();

    return [
      'order_number' => $order->order_number,
      'status' => $order->status?->value,
      'status_label' => $order->status?->label(),
      'payment_method' => $order->payment_method ? $this->paymentCode($order->payment_method) : null,
      'payment_method_label' => $order->payment_method?->label(),
      'payment_status' => $order->payment?->status?->value,
      'fulfillment_type' => $order->fulfillment_type,
      'source' => $order->source,
      'items' => $order->items->map(fn ($item) => [
        'name' => $item->product_name . ($item->variant_name ? ' (' . $item->variant_name . ')' : ''),
        'sku' => $item->sku,
        'quantity' => (int) $item->quantity,
        'line_total' => $this->currency->formatOrderAmount((float) $item->total_price, $currency),
      ])->values()->all(),
      'currency' => $currency,
      'subtotal' => $this->currency->formatOrderAmount((float) $order->subtotal, $currency),
      'shipping' => $this->currency->formatOrderAmount((float) $order->shipping_amount, $currency),
      'discount' => $this->currency->formatOrderAmount((float) $order->discount_amount, $currency),
      'total' => $this->currency->formatOrderAmount((float) $order->total, $currency),
      'total_value' => (float) $order->total,
      'tracking_number' => $order->tracking_number,
      'created_at' => $order->created_at?->toIso8601String(),
      'shipped_at' => $order->shipped_at?->toIso8601String(),
      'delivered_at' => $order->delivered_at?->toIso8601String(),
      'card_payment_url' => $this->paymentUrl($order),
    ];
  }

  /**
   * Lien de paiement à envoyer sur WhatsApp, s'il est encore utilisable.
   *
   * @param Order $order Commande
   * @return string|null URL /payer/{token}
   */
  public function paymentUrl(Order $order): ?string
  {
    if (
      !$order->payment_token
      || $order->status !== OrderStatus::Pending
      || $order->payment_method === PaymentMethod::Cod
      || ($order->payment_token_expires_at && $order->payment_token_expires_at->isPast())
    ) {
      return null;
    }

    return route('bot.pay.show', ['token' => $order->payment_token]);
  }

  /**
   * Envoie la demande de paiement Mobile Money (push FlexPay) sur le
   * téléphone de la cliente, directement depuis la conversation WhatsApp.
   *
   * @param Order $order Commande en attente
   * @param string|null $payerPhone Numéro à débiter (par défaut celui de la commande)
   * @param string|null $operatorCode Opérateur (mpesa, airtel, orange, afrimoney) ; deviné sinon
   * @param string|null $currency Devise à débiter (CDF ou USD) ; la commande est convertie si besoin
   * @return array<string, mixed> Résultat lisible par le bot
   */
  public function payMobileMoney(Order $order, ?string $payerPhone = null, ?string $operatorCode = null, ?string $currency = null): array
  {
    $this->ensurePayable($order);

    $phone = $payerPhone ?: ($order->payment?->metadata['mobile_money_phone'] ?? $order->user?->phone ?? '');
    $normalized = $this->normalizePhone((string) $phone);

    $operator = $operatorCode ? MobileMoneyOperator::tryFrom($operatorCode) : null;
    $operator ??= $this->guessOperator($normalized);

    if (!$operator) {
      throw ValidationException::withMessages([
        'payer_phone' => 'Numéro Mobile Money non reconnu (M-Pesa 081-084, Orange 085-089, Airtel 097-099, Afrimoney 090-091).',
      ]);
    }

    $normalized = $this->mobileMoney->validatePhoneForOperator($normalized, $operator, 'payer_phone');

    if (!$this->settings->isPaymentMethodEnabled(PaymentMethod::MobileMoney)) {
      throw ValidationException::withMessages(['payment_method' => 'Le paiement Mobile Money n\'est pas disponible.']);
    }

    if ($currency) {
      $order = $this->switchCurrency($order, $currency);
    }

    if ($order->payment_method !== PaymentMethod::MobileMoney) {
      $order->update(['payment_method' => PaymentMethod::MobileMoney]);
      $order->payment?->update(['method' => PaymentMethod::MobileMoney]);
    }

    $this->payments->requestFlexPayMobilePayment($order->fresh(['payment']), $normalized, $operator);

    $order = $order->fresh(['items', 'payment']);

    return [
      'push_sent' => $order->status === OrderStatus::Pending,
      'paid' => $order->status !== OrderStatus::Pending,
      'operator' => $operator->label(),
      'payer_phone' => '+' . $normalized,
      'amount' => $this->currency->formatOrderAmount((float) $order->total, $order->currency),
      'currency' => $order->currency,
      'instructions' => $order->status === OrderStatus::Pending
        ? 'Une demande de paiement ' . $operator->label() . ' a été envoyée au +' . $normalized . '. La cliente doit la valider avec son code secret ; la confirmation arrive automatiquement.'
        : 'Paiement confirmé.',
      'order' => $this->orderPayload($order),
    ];
  }

  /**
   * Revérifie auprès de FlexPay si le paiement Mobile Money a été validé.
   *
   * @param Order $order Commande
   * @return array<string, mixed> Statut à jour
   */
  public function verifyPayment(Order $order): array
  {
    if ($order->status === OrderStatus::Pending) {
      try {
        $order = $this->payments->verifyAndConfirmFlexPay($order->fresh(['payment']));
      } catch (ValidationException) {
        // Paiement pas encore validé : on renvoie simplement le statut.
      }
    }

    $order = $order->fresh(['items', 'payment']);

    return [
      'paid' => !in_array($order->status, [OrderStatus::Pending, OrderStatus::Cancelled], true),
      'order' => $this->orderPayload($order),
    ];
  }

  /**
   * Lien de paiement par carte à envoyer sur WhatsApp (renouvelé si expiré).
   *
   * @param Order $order Commande en attente
   * @param string|null $currency Devise de paiement (CDF ou USD) ; la commande est convertie si besoin
   * @return string URL /payer/{token}
   */
  public function cardPaymentUrl(Order $order, ?string $currency = null): string
  {
    $this->ensurePayable($order);

    if (!$this->settings->isPaymentMethodEnabled(PaymentMethod::Stripe)) {
      throw ValidationException::withMessages(['payment_method' => 'Le paiement par carte n\'est pas disponible.']);
    }

    if ($currency) {
      $order = $this->switchCurrency($order, $currency);
    }

    if (!$order->payment_token || ($order->payment_token_expires_at && $order->payment_token_expires_at->isPast())) {
      $order->update([
        'payment_token' => Str::random(48),
        'payment_token_expires_at' => now()->addHours(max(1, (int) config('bot.payment_link_ttl_hours', 72))),
      ]);
    }

    return route('bot.pay.show', ['token' => $order->payment_token]);
  }

  /**
   * Convertit une commande en attente dans une autre devise (CDF ↔ USD) :
   * lignes, sous-total, livraison, remise, total et montant du paiement.
   * La conversion passe par la devise de base du catalogue, aux taux du site.
   *
   * @param Order $order Commande en attente
   * @param string $currency Devise souhaitée
   * @return Order Commande à jour
   */
  public function switchCurrency(Order $order, string $currency): Order
  {
    $this->ensurePayable($order);

    $target = strtoupper(trim($currency));
    $from = strtoupper((string) ($order->currency ?: $this->currency->primaryCurrency()));

    if (!in_array($target, self::PAYMENT_CURRENCIES, true)) {
      throw ValidationException::withMessages([
        'currency' => 'Devise non prise en charge. Choisissez CDF (francs congolais) ou USD (dollars).',
      ]);
    }

    if ($target === $from) {
      return $order->fresh(['items', 'payment']);
    }

    $convert = fn ($amount): float => $this->currency->convertFromEur(
      $this->currency->convertToEur((float) $amount, $from),
      $target
    );

    DB::transaction(function () use ($order, $target, $convert): void {
      foreach ($order->items as $item) {
        $item->update([
          'unit_price' => $convert($item->unit_price),
          'total_price' => $convert($item->total_price),
        ]);
      }

      $subtotal = $convert($order->subtotal);
      $shipping = $convert($order->shipping_amount);
      $discount = $convert($order->discount_amount);
      $tax = $convert($order->tax_amount);
      $total = round(max(0, $subtotal + $shipping + $tax - $discount), $target === 'CDF' ? 0 : 2);

      $order->update([
        'currency' => $target,
        'subtotal' => $subtotal,
        'shipping_amount' => $shipping,
        'discount_amount' => $discount,
        'tax_amount' => $tax,
        'total' => $total,
      ]);

      $order->payment?->update([
        'amount' => $total,
        'currency' => $target,
        'metadata' => array_merge($order->payment->metadata ?? [], [
          'rate_eur' => $this->currency->getRateFromEur($target),
        ]),
      ]);
    });

    return $order->fresh(['items', 'payment']);
  }

  /**
   * Devine l'opérateur Mobile Money à partir du préfixe.
   *
   * @param string $normalized Numéro 243XXXXXXXXX
   * @return MobileMoneyOperator|null Opérateur
   */
  public function guessOperator(string $normalized): ?MobileMoneyOperator
  {
    if (strlen($normalized) !== 12) {
      return null;
    }

    $national = substr($normalized, 3);

    foreach (MobileMoneyOperator::cases() as $operator) {
      foreach ($operator->nationalPrefixes() as $prefix) {
        if (str_starts_with($national, $prefix)) {
          return $operator;
        }
      }
    }

    return null;
  }

  /**
   * Liste les commandes d'une cliente (les plus récentes d'abord).
   *
   * @param string $phone Numéro WhatsApp
   * @param int $limit Nombre maximum
   * @return array<int, array<string, mixed>> Commandes
   */
  public function ordersForPhone(string $phone, int $limit = 5): array
  {
    $user = $this->findCustomer($phone);

    if (!$user) {
      return [];
    }

    return $user->orders()
      ->with(['items', 'payment'])
      ->latest()
      ->limit($limit)
      ->get()
      ->map(fn (Order $order) => $this->orderPayload($order))
      ->all();
  }

  /**
   * @param Order $order Commande
   * @return void
   */
  private function ensurePayable(Order $order): void
  {
    if ($order->status !== OrderStatus::Pending) {
      throw ValidationException::withMessages([
        'order' => $order->status === OrderStatus::Cancelled
          ? 'Cette commande a été annulée.'
          : 'Cette commande est déjà payée ou en cours de traitement.',
      ]);
    }

    if ($order->payment_method === PaymentMethod::Cod) {
      throw ValidationException::withMessages([
        'order' => 'Cette commande est réglée à la livraison.',
      ]);
    }
  }

  /**
   * Vérifie un code promo pour un panier donné.
   *
   * @param string $code Code saisi par la cliente
   * @param array<int, array<string, mixed>> $items Lignes {sku, quantity}
   * @param string|null $phone Numéro de la cliente
   * @return array<string, mixed> Résultat
   */
  public function checkCoupon(string $code, array $items, ?string $phone = null): array
  {
    $lines = $this->resolveLines($items);
    $subtotalEur = (float) $lines->sum(fn (array $line) => $line['unit_price_eur'] * $line['quantity']);
    $user = $phone ? $this->findCustomer($phone) : null;

    $coupon = $this->coupons->validateForCheckout($code, $subtotalEur, $user);
    $discountEur = $this->coupons->calculateDiscountEur($coupon, $subtotalEur);

    return [
      'valid' => true,
      'code' => $coupon->code,
      'discount' => $this->money($discountEur),
    ];
  }

  /* ------------------------------------------------------------------ */
  /* Outils internes                                                     */
  /* ------------------------------------------------------------------ */

  /**
   * Résout les lignes {sku, quantity} en produits/variantes avec prix.
   *
   * @param array<int, array<string, mixed>> $items Lignes demandées
   * @return Collection<int, array<string, mixed>> Lignes résolues
   */
  private function resolveLines(array $items): Collection
  {
    return collect($items)->map(function (array $item, int $index) {
      $sku = trim((string) ($item['sku'] ?? ''));
      $quantity = max(1, (int) ($item['quantity'] ?? 1));

      $variant = ProductVariant::query()->with('product')->where('sku', $sku)->where('is_active', true)->first();
      $product = $variant?->product ?? $this->findProduct($sku);

      if (!$product || !$product->is_active) {
        throw ValidationException::withMessages([
          "items.$index.sku" => "Produit introuvable : « $sku ».",
        ]);
      }

      if (!$variant && !empty($item['variant'])) {
        $variant = $product->variants()
          ->where('is_active', true)
          ->where(fn ($q) => $q->where('sku', $item['variant'])->orWhere('name', $item['variant']))
          ->first();
      }

      return [
        'product' => $product,
        'variant' => $variant,
        'sku' => $variant?->sku ?? $product->sku,
        'name' => $product->name . ($variant ? ' (' . $variant->name . ')' : ''),
        'quantity' => $quantity,
        'unit_price_eur' => (float) ($variant?->price ?? $product->price),
      ];
    })->values();
  }

  /**
   * Remise applicable : code promo s'il est fourni, sinon remise kit.
   *
   * @param Collection<int, array<string, mixed>> $lines Lignes
   * @param float $subtotalEur Sous-total
   * @param string|null $couponCode Code promo
   * @param User|null $user Cliente
   * @return array{0: float, 1: string|null} Montant et libellé
   */
  private function discount(Collection $lines, float $subtotalEur, ?string $couponCode, ?User $user): array
  {
    if ($couponCode) {
      $coupon = $this->coupons->validateForCheckout($couponCode, $subtotalEur, $user);

      return [$this->coupons->calculateDiscountEur($coupon, $subtotalEur), 'Code promo ' . $coupon->code];
    }

    $kit = $this->kitDiscountEur($lines);

    return [$kit, $kit > 0 ? 'Remise routine complète (-' . $this->kitDiscountPercent() . ' %)' : null];
  }

  /**
   * Remise kit : un exemplaire de chaque produit d'une routine complète.
   *
   * @param Collection<int, array<string, mixed>> $lines Lignes
   * @return float Montant de la remise (devise de base)
   */
  private function kitDiscountEur(Collection $lines): float
  {
    $percent = $this->kitDiscountPercent();

    if ($percent <= 0) {
      return 0.0;
    }

    $skus = $lines->map(fn (array $line) => $line['product']->sku)->unique();
    $discount = 0.0;

    foreach ($this->routines() as $routine) {
      $routineSkus = collect($routine['products'])->pluck('sku');

      if ($routineSkus->diff($skus)->isEmpty()) {
        $routineTotal = (float) $lines
          ->filter(fn (array $line) => $routineSkus->contains($line['product']->sku))
          ->unique(fn (array $line) => $line['product']->sku)
          ->sum('unit_price_eur');

        $discount += $routineTotal * $percent / 100;
      }
    }

    return round($discount, 2);
  }

  /**
   * @return float Pourcentage de remise kit (0–50)
   */
  private function kitDiscountPercent(): float
  {
    return min(50.0, max(0.0, (float) config('bot.kit_discount_percent', 0)));
  }

  /**
   * @param string|null $currency Devise demandée
   * @return string Devise disponible
   */
  private function resolveCurrency(?string $currency): string
  {
    $currency = strtoupper((string) $currency);

    return in_array($currency, [...self::PAYMENT_CURRENCIES, ...$this->currency->availableCurrencies()], true)
      ? $currency
      : $this->currency->primaryCurrency();
  }

  /**
   * @param string $type delivery ou pickup
   * @return string Mode de remise validé
   */
  private function resolveFulfillment(string $type): string
  {
    if ($type === 'pickup') {
      if (!$this->settings->isPickupEnabled()) {
        throw ValidationException::withMessages([
          'fulfillment_type' => 'Le retrait en boutique n\'est pas disponible.',
        ]);
      }

      return 'pickup';
    }

    return 'delivery';
  }

  /**
   * @param string $code mobile_money, card ou cod
   * @return PaymentMethod Méthode activée
   */
  private function resolvePaymentMethod(string $code): PaymentMethod
  {
    $method = match ($code) {
      'card' => PaymentMethod::Stripe,
      'cod' => PaymentMethod::Cod,
      default => PaymentMethod::MobileMoney,
    };

    if (!$this->settings->isPaymentMethodEnabled($method)) {
      throw ValidationException::withMessages([
        'payment_method' => 'Ce moyen de paiement n\'est pas disponible.',
      ]);
    }

    return $method;
  }

  /**
   * @param PaymentMethod $method Méthode
   * @return string Code exposé au bot
   */
  private function paymentCode(PaymentMethod $method): string
  {
    return match ($method) {
      PaymentMethod::Stripe => 'card',
      PaymentMethod::Cod => 'cod',
      PaymentMethod::MobileMoney => 'mobile_money',
      default => $method->value,
    };
  }

  /**
   * @param string $name Nom complet
   * @return array{0: string, 1: string} Prénom, nom
   */
  private function splitName(string $name): array
  {
    $parts = preg_split('/\s+/', trim($name)) ?: [];
    $first = array_shift($parts) ?? 'Cliente';

    return [$first, implode(' ', $parts) ?: '-'];
  }

  /**
   * @param array<string, mixed> $data Données commande
   * @return string Note interne visible dans l'admin
   */
  private function notes(array $data): string
  {
    $note = 'Commande WhatsApp (bot).';

    if (!empty($data['notes'])) {
      $note .= ' ' . trim((string) $data['notes']);
    }

    return $note;
  }

  /**
   * @return string Code pays de livraison
   */
  private function country(): string
  {
    return (string) config('bot.country', 'CD');
  }

  /**
   * Montant dans les deux devises du site.
   *
   * @param float $amountEur Montant en devise de base
   * @return array<string, mixed> {cdf, usd, label}
   */
  private function money(float $amountEur): array
  {
    $cdf = $this->currency->convertFromEur($amountEur, 'CDF');
    $usd = $this->currency->convertFromEur($amountEur, 'USD');

    return [
      'cdf' => $cdf,
      'usd' => $usd,
      'label' => $this->currency->format($cdf, 'CDF') . ' (' . $this->currency->format($usd, 'USD') . ')',
    ];
  }

  /**
   * Montant dans une devise donnée.
   *
   * @param float $amountEur Montant en devise de base
   * @param string $currency Devise
   * @return array<string, mixed> {value, label}
   */
  private function amount(float $amountEur, string $currency): array
  {
    $value = $this->currency->convertFromEur($amountEur, $currency);

    return ['value' => $value, 'label' => $this->currency->format($value, $currency)];
  }

  /**
   * @param float $amountEur Montant
   * @param string $currency Devise principale du devis
   * @return string|null Libellé dans l'autre devise
   */
  private function otherCurrencyAmount(float $amountEur, string $currency): ?string
  {
    $other = $currency === 'CDF' ? 'USD' : 'CDF';

    return $this->currency->formatFromEur($amountEur, $other);
  }

  /**
   * @param string|null $url URL relative ou absolue
   * @return string|null URL absolue
   */
  private function absoluteUrl(?string $url): ?string
  {
    if (!$url) {
      return null;
    }

    return str_starts_with($url, 'http') ? $url : url($url);
  }
}
