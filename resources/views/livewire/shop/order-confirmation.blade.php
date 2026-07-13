@if ($order)
  <div>
    <x-shopwise-breadcrumb
      title="Commande confirmée"
      :items="[['label' => 'Commande confirmée', 'url' => route('checkout.success')]]"
    />

    <div class="main_content">
      <div class="section">
        <div class="container">
          <div class="row justify-content-center">
            <div class="col-md-8">
              <div class="text-center order_complete">
                <i class="fas fa-check-circle"></i>
                <div class="heading_s1">
                  <h3>Votre commande est confirmée !</h3>
                </div>
                <p>
                  Merci pour votre commande <strong>{{ $order->order_number }}</strong>.
                  Elle est en cours de traitement et sera finalisée sous 3 à 6 heures.
                  Un e-mail de confirmation vous sera envoyé.
                </p>
                <p class="mb-4">
                  Total payé :
                  <strong>{{ $currencyService->format($order->total, $order->currency) }}</strong>
                </p>
                <a href="{{ route('shop.catalog') }}" class="btn btn-fill-out me-2">Continuer mes achats</a>
                @auth
                  <a href="{{ route('account.orders.show', $order) }}" class="btn btn-line-fill">Voir ma commande</a>
                @endauth
              </div>

              <div class="card mt-4">
                <div class="card-header">
                  <h3 class="mb-0">Récapitulatif</h3>
                </div>
                <div class="card-body">
                  <div class="table-responsive">
                    <table class="table">
                      <thead>
                        <tr>
                          <th>Produit</th>
                          <th class="text-end">Total</th>
                        </tr>
                      </thead>
                      <tbody>
                        @foreach ($order->items as $item)
                          <tr>
                            <td>{{ $item->product_name }} <span class="product-qty">× {{ $item->quantity }}</span></td>
                            <td class="text-end">{{ $currencyService->format($item->total_price, $order->currency) }}</td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>

                  @if ($order->shippingAddress)
                    <address class="mb-0 mt-3">
                      <strong>Livraison à :</strong><br>
                      {{ $order->shippingAddress->fullName() }}<br>
                      {{ $order->shippingAddress->address_line_1 }}<br>
                      {{ $order->shippingAddress->postal_code }} {{ $order->shippingAddress->city }}
                    </address>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif
