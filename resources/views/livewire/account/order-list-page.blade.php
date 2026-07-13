<div>
  <x-shopwise-account-shell
    title="Mes commandes"
    :breadcrumb-items="[['label' => 'Mon compte', 'url' => route('account.dashboard')], ['label' => 'Commandes', 'url' => route('account.orders')]]"
  >
    <div class="card">
      <div class="card-header">
        <h3>Commandes</h3>
      </div>
      <div class="card-body">
        @if ($orders->isEmpty())
          <p class="text-center mb-0">Aucune commande pour le moment.</p>
          <div class="text-center mt-3">
            <a href="{{ route('shop.catalog') }}" class="btn btn-fill-out btn-sm">Parcourir la boutique</a>
          </div>
        @else
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Commande</th>
                  <th>Date</th>
                  <th>Statut</th>
                  <th>Total</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($orders as $order)
                  <tr wire:key="order-row-{{ $order->id }}">
                    <td>{{ $order->order_number }}</td>
                    <td>{{ $order->created_at->format('d/m/Y') }}</td>
                    <td>{{ $order->status->label() }}</td>
                    <td>{{ $currencyService->format($order->total, $order->currency) }} pour {{ $order->items_count }} article{{ $order->items_count > 1 ? 's' : '' }}</td>
                    <td>
                      <a href="{{ route('account.orders.show', $order) }}" class="btn btn-fill-out btn-sm">Voir</a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
          <div class="mt-3">
            {{ $orders->links('pagination::bootstrap-5') }}
          </div>
        @endif
      </div>
    </div>
  </x-shopwise-account-shell>
</div>
