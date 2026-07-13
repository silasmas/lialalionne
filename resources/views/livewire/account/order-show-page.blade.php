<div>
  <x-shopwise-account-shell
    :title="'Commande ' . $order->order_number"
    :breadcrumb-items="[
      ['label' => 'Mon compte', 'url' => route('account.dashboard')],
      ['label' => 'Commandes', 'url' => route('account.orders')],
      ['label' => $order->order_number, 'url' => route('account.orders.show', $order)],
    ]"
  >
    <div class="card mb-4">
      <div class="card-header">
        <h3>Commande {{ $order->order_number }}</h3>
      </div>
      <div class="card-body">
        <p class="mb-0">Passée le {{ $order->created_at->format('d/m/Y à H:i') }}</p>
      </div>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card mb-4">
          <div class="card-header">
            <h3>Articles</h3>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Produit</th>
                    <th>Qté</th>
                    <th class="text-end">Total</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($order->items as $item)
                    <tr>
                      <td>
                        {{ $item->product_name }}
                        @if ($item->variant_name)
                          <small class="d-block text-muted">{{ $item->variant_name }}</small>
                        @endif
                      </td>
                      <td>{{ $item->quantity }}</td>
                      <td class="text-end">{{ $currencyService->format($item->total_price, $order->currency) }}</td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          </div>
        </div>

        @if ($order->shippingAddress)
          <div class="card">
            <div class="card-header">
              <h3>Adresse de livraison</h3>
            </div>
            <div class="card-body">
              <address class="mb-0">
                {{ $order->shippingAddress->fullName() }}<br>
                {{ $order->shippingAddress->address_line_1 }}<br>
                @if ($order->shippingAddress->address_line_2)
                  {{ $order->shippingAddress->address_line_2 }}<br>
                @endif
                {{ $order->shippingAddress->postal_code }} {{ $order->shippingAddress->city }}<br>
                {{ $order->shippingAddress->country }}
              </address>
            </div>
          </div>
        @endif
      </div>

      <div class="col-lg-4">
        <div class="card">
          <div class="card-header">
            <h3>Récapitulatif</h3>
          </div>
          <div class="card-body">
            <table class="table mb-0">
              <tbody>
                <tr>
                  <td>Statut</td>
                  <td class="text-end">{{ $order->status->label() }}</td>
                </tr>
                <tr>
                  <td>Sous-total</td>
                  <td class="text-end">{{ $currencyService->format($order->subtotal, $order->currency) }}</td>
                </tr>
                <tr>
                  <td>Livraison</td>
                  <td class="text-end">{{ $currencyService->format($order->shipping_amount, $order->currency) }}</td>
                </tr>
                <tr>
                  <td><strong>Total</strong></td>
                  <td class="text-end"><strong>{{ $currencyService->format($order->total, $order->currency) }}</strong></td>
                </tr>
              </tbody>
            </table>

            @if ($order->tracking_number)
              <div class="mt-3 pt-3 border-top">
                <strong>Numéro de suivi</strong>
                <p class="mb-0">{{ $order->tracking_number }}</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </div>
  </x-shopwise-account-shell>
</div>
