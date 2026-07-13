<div>
  <x-shopwise-breadcrumb
    title="Checkout"
    :items="[['label' => 'Checkout', 'url' => route('shop.checkout')]]"
  />

  <div class="main_content">
    @if ($checkoutPhase === 'mobile_money' && $pendingOrder)
      <div
        class="section"
        @if ($livePaymentEnabled && $mobileMoneyPushSent)
          wire:init="pollMobileMoneyPayment"
          wire:poll.3s="pollMobileMoneyPayment"
        @endif
      >
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-lg-8">
              @error('checkout')
                <div class="alert alert-danger">{{ $message }}</div>
              @enderror

              <div class="order_review">
                <div class="heading_s1">
                  <h4>Paiement Mobile Money</h4>
                </div>

                <p class="text-muted mb-3">
                  Commande <strong>{{ $pendingOrder->order_number }}</strong> —
                  {{ $currencyService->format($pendingOrder->total, $pendingOrder->currency) }}
                  · {{ $mobileMoneyOperatorLabel }}
                  @if ($mobileMoneyPhone)
                    · {{ $mobileMoneyPhone }}
                  @endif
                </p>

                <x-payment-steps theme="shopwise" :current-step="$this->currentMobileMoneyStep" />

                @if ($livePaymentEnabled && $mobileMoneyPushSent)
                  <div class="mm-payment-status mt-3" aria-live="polite">
                    @if ($isCheckingMobileMoneyPayment)
                      <span class="mm-payment-status__spinner" aria-hidden="true"></span>
                    @endif
                    <span class="mm-payment-status__text">
                      {{ $mobileMoneyLiveStatus ?? 'Confirmez le paiement sur votre téléphone…' }}
                    </span>
                  </div>
                @endif

                @if ($showMobileMoneyVerifyButton && $livePaymentEnabled)
                  <button
                    type="button"
                    wire:click="checkMobileMoneyPayment"
                    class="btn btn-fill-out btn-block mt-3"
                    wire:loading.attr="disabled"
                  >
                    <span wire:loading.remove wire:target="checkMobileMoneyPayment">Vérifier le paiement</span>
                    <span wire:loading wire:target="checkMobileMoneyPayment">Vérification…</span>
                  </button>
                @endif

                <a
                  href="{{ route('checkout.cancel', $pendingOrder) }}"
                  class="d-block text-center text-muted mt-3 small"
                >
                  Annuler et revenir
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    @else
      <form wire:submit="placeOrder" novalidate>
        <div class="section">
          <div class="container">
            @error('checkout')
              <div class="alert alert-danger">{{ $message }}</div>
            @enderror

            @guest
              <div class="row">
                <div class="col-lg-6">
                  <div class="toggle_info">
                    <span>
                      <i class="fas fa-user"></i>
                      Déjà client ?
                      <a href="{{ route('account.login') }}">Connectez-vous ici</a>
                    </span>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <div class="medium_divider"></div>
                </div>
              </div>
            @endguest

            <div class="row">
              <div class="col-12">
                <div class="medium_divider"></div>
                <div class="divider center_icon"><i class="linearicons-credit-card"></i></div>
                <div class="medium_divider"></div>
              </div>
            </div>

            <div class="row">
              <div class="col-md-6">
                <div class="heading_s1">
                  <h4>Informations de facturation</h4>
                </div>

                <div class="form-group mb-3">
                  <input type="text" wire:model.live="firstName" class="form-control @error('firstName') is-invalid @enderror" placeholder="Prénom *">
                  @error('firstName') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="form-group mb-3">
                  <input type="text" wire:model.live="lastName" class="form-control @error('lastName') is-invalid @enderror" placeholder="Nom *">
                  @error('lastName') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="form-group mb-3">
                  <input type="email" wire:model.live="email" class="form-control @error('email') is-invalid @enderror" placeholder="E-mail *">
                  @error('email') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
                <div class="form-group mb-3">
                  <input type="tel" wire:model.live="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="Téléphone *">
                  @error('phone') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>

                <div class="heading_s1 mt-4">
                  <h4>Mode de réception</h4>
                </div>
                <div class="form-group mb-3">
                  <div class="custome-radio mb-2">
                    <input class="form-check-input" type="radio" wire:model.live="fulfillmentType" value="delivery" id="fulfillmentDelivery">
                    <label class="form-check-label" for="fulfillmentDelivery">Livraison à domicile</label>
                  </div>
                  @if ($pickupEnabled)
                    <div class="custome-radio">
                      <input class="form-check-input" type="radio" wire:model.live="fulfillmentType" value="pickup" id="fulfillmentPickup">
                      <label class="form-check-label" for="fulfillmentPickup">
                        Retrait en boutique — {{ $pickupStoreName }} (gratuit)
                      </label>
                    </div>
                  @endif
                  @error('fulfillmentType') <div class="text-danger small">{{ $message }}</div> @enderror
                </div>

                @if ($fulfillmentType === 'delivery')
                  <div class="form-group mb-3">
                    <input type="text" wire:model.live="addressLine1" class="form-control @error('addressLine1') is-invalid @enderror" placeholder="Adresse *">
                    @error('addressLine1') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                  </div>
                  <div class="form-group mb-3">
                    <input type="text" wire:model.live="addressLine2" class="form-control" placeholder="Complément d'adresse (optionnel)">
                  </div>
                  <div class="form-group mb-3">
                    <input type="text" wire:model.live="city" class="form-control @error('city') is-invalid @enderror" placeholder="Ville *">
                    @error('city') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                  </div>
                  <div class="form-group mb-3">
                    <input type="text" wire:model.live="postalCode" class="form-control @error('postalCode') is-invalid @enderror" placeholder="Code postal *">
                    @error('postalCode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                  </div>
                  <div class="form-group mb-3">
                    <div class="custom_select">
                      <select wire:model.live="country" class="form-control @error('country') is-invalid @enderror">
                        <option value="CD">République Démocratique du Congo</option>
                      </select>
                    </div>
                    @error('country') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                  </div>

                  <div class="heading_s1">
                    <h4>Mode de livraison</h4>
                  </div>
                  <div class="form-group mb-3">
                    @forelse ($shippingRates as $rate)
                      <div class="custome-radio mb-2">
                        <input class="form-check-input" type="radio" wire:model.live="shippingRateId" value="{{ $rate->id }}" id="shippingRate{{ $rate->id }}">
                        <label class="form-check-label" for="shippingRate{{ $rate->id }}">
                          {{ $rate->name }}
                          @if ($rate->estimated_days_min)
                            ({{ $rate->estimated_days_min }}–{{ $rate->estimated_days_max }} jours)
                          @endif
                          —
                          {{ $rate->price > 0 ? $currencyService->formatFromEur((float) $rate->price, $currency) : 'Gratuit' }}
                        </label>
                      </div>
                    @empty
                      <p class="text-muted small">Aucune option de livraison pour ce pays.</p>
                    @endforelse
                    @error('shippingRateId') <div class="text-danger small">{{ $message }}</div> @enderror
                  </div>
                @endif

                <div class="heading_s1">
                  <h4>Informations complémentaires</h4>
                </div>
                <div class="form-group mb-0">
                  <textarea wire:model.live="notes" rows="4" class="form-control @error('notes') is-invalid @enderror" placeholder="Notes de commande (optionnel)"></textarea>
                  @error('notes') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
              </div>

              <div class="col-md-6">
                <div class="order_review">
                  <div class="heading_s1">
                    <h4>Votre commande</h4>
                  </div>

                  @if ($currencyMode === 'dual')
                    <div class="form-group mb-3">
                      <label class="small text-muted">Devise de paiement</label>
                      <select wire:model.live="currency" class="form-control form-control-sm @error('currency') is-invalid @enderror">
                        @foreach ($availableCurrencies as $code)
                          <option value="{{ $code }}">{{ $code === 'CDF' ? 'Franc congolais (CDF)' : 'Dollar (USD)' }}</option>
                        @endforeach
                      </select>
                      @error('currency') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                    </div>
                  @endif

                  <div class="table-responsive order_table">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Produit</th>
                          <th>Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($cartItems as $item)
                          <tr wire:key="checkout-item-{{ $item->id }}">
                            <td>
                              {{ $item->product->name }}
                              @if ($item->variant)
                                <small class="d-block text-muted">{{ $item->variant->name }}</small>
                              @endif
                              <span class="product-qty">× {{ $item->quantity }}</span>
                            </td>
                            <td>{{ $item->product->formatPrice($item->lineTotal()) }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                      <tfoot>
                        <tr>
                          <th>Sous-total</th>
                          <td class="product-subtotal">{{ $currencyService->format($subtotal, $currency) }}</td>
                        </tr>
                        <tr>
                          <th>{{ $fulfillmentType === 'pickup' ? 'Retrait' : 'Livraison' }}</th>
                          <td>{{ $fulfillmentType === 'pickup' ? 'Gratuit' : $currencyService->format($shippingAmount, $currency) }}</td>
                        </tr>
                        <tr>
                          <th>Total</th>
                          <td class="product-subtotal"><strong>{{ $currencyService->format($total, $currency) }}</strong></td>
                        </tr>
                      </tfoot>
                    </table>
                  </div>

                  @if (count($paymentMethods) > 0)
                    <div class="payment_method">
                      <div class="heading_s1">
                        <h4>Paiement</h4>
                      </div>
                      <div class="payment_option">
                        @foreach ($paymentMethods as $method)
                          <div class="custome-radio mb-3">
                            <input
                              class="form-check-input"
                              type="radio"
                              wire:model.live="paymentMethod"
                              value="{{ $method->value }}"
                              id="payment{{ $method->value }}"
                            >
                            <label class="form-check-label" for="payment{{ $method->value }}">{{ $method->label() }}</label>
                            @if ($method === \App\Enums\PaymentMethod::MobileMoney && $paymentMethod === 'mobile_money')
                              <p class="payment-text">M-Pesa, Airtel Money, Orange Money ou Afri Money — confirmation sur votre téléphone.</p>
                            @elseif ($method === \App\Enums\PaymentMethod::Stripe && $paymentMethod === 'stripe')
                              <p class="payment-text">Paiement sécurisé par carte bancaire.</p>
                            @elseif ($method === \App\Enums\PaymentMethod::Cod && $paymentMethod === 'cod')
                              <p class="payment-text">Réglez votre commande à la livraison ou au retrait.</p>
                            @endif
                          </div>
                        @endforeach
                        @error('paymentMethod') <div class="text-danger small">{{ $message }}</div> @enderror
                      </div>

                      @if ($paymentMethod === 'mobile_money')
                        <x-mobile-money-operators
                          theme="shopwise"
                          :selected-operator="$mobileMoneyOperator"
                        />
                      @endif
                    </div>
                  @endif

                  <button type="submit" class="btn btn-fill-out btn-block" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="placeOrder">Passer commande</span>
                    <span wire:loading wire:target="placeOrder">Traitement...</span>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </form>
    @endif
  </div>
</div>
