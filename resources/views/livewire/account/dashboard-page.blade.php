<div>
  <x-shopwise-account-shell
    title="Mon compte"
    :breadcrumb-items="[['label' => 'Mon compte', 'url' => route('account.dashboard')]]"
  >
    <div class="tab-content dashboard_content">
      <div class="card">
        <div class="card-header">
          <h3>Tableau de bord</h3>
        </div>
        <div class="card-body">
          <p>
            Bonjour <strong>{{ $user->name }}</strong>, bienvenue dans votre espace client.
            Consultez vos <a href="{{ route('account.orders') }}">commandes récentes</a>,
            vos <a href="{{ route('account.favorites') }}">favoris</a>
            ou passez une nouvelle commande dans la <a href="{{ route('shop.catalog') }}">boutique</a>.
          </p>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header">
          <h3>Mes informations</h3>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-md-6 mb-3">
              <strong>E-mail</strong>
              <p class="mb-0">{{ $user->email }}</p>
            </div>
            @if ($user->phone)
              <div class="col-md-6 mb-3">
                <strong>Téléphone</strong>
                <p class="mb-0">{{ $user->phone }}</p>
              </div>
            @endif
          </div>
          <p class="text-muted small mb-0">Connexion sécurisée par code OTP — aucun mot de passe requis.</p>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header">
          <h3>Adresse de livraison</h3>
        </div>
        <div class="card-body">
          <p class="text-muted small">
            Enregistrez votre adresse pour la préremplir automatiquement au checkout lorsque vous choisissez la livraison à domicile.
          </p>
          <form wire:submit="saveDeliveryAddress" novalidate>
            <div class="form-group mb-3">
              <input
                type="text"
                wire:model.live="deliveryAddressLine1"
                class="form-control @error('deliveryAddressLine1') is-invalid @enderror"
                placeholder="Adresse *"
              >
              @error('deliveryAddressLine1') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="form-group mb-3">
              <input
                type="text"
                wire:model.live="deliveryAddressLine2"
                class="form-control @error('deliveryAddressLine2') is-invalid @enderror"
                placeholder="Complément d'adresse (optionnel)"
              >
              @error('deliveryAddressLine2') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <input
                    type="text"
                    wire:model.live="deliveryCity"
                    class="form-control @error('deliveryCity') is-invalid @enderror"
                    placeholder="Ville *"
                  >
                  @error('deliveryCity') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <input
                    type="text"
                    wire:model.live="deliveryPostalCode"
                    class="form-control @error('deliveryPostalCode') is-invalid @enderror"
                    placeholder="Code postal *"
                  >
                  @error('deliveryPostalCode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                </div>
              </div>
            </div>
            <div class="form-group mb-3">
              <select wire:model.live="deliveryCountry" class="form-control @error('deliveryCountry') is-invalid @enderror">
                <option value="CD">République Démocratique du Congo</option>
              </select>
              @error('deliveryCountry') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
            </div>
            <button type="submit" class="btn btn-fill-out" wire:loading.attr="disabled">
              <span wire:loading.remove wire:target="saveDeliveryAddress">Enregistrer l'adresse</span>
              <span wire:loading wire:target="saveDeliveryAddress">Enregistrement…</span>
            </button>
          </form>
        </div>
      </div>

      <div class="card mt-4">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h3 class="mb-0">Commandes récentes</h3>
          <a href="{{ route('account.orders') }}" class="btn btn-fill-out btn-sm">Tout voir</a>
        </div>
        <div class="card-body">
          @if ($recentOrders->isEmpty())
            <p class="mb-0">Vous n'avez pas encore passé de commande.</p>
            <a href="{{ route('shop.catalog') }}" class="btn btn-fill-out btn-sm mt-3">Découvrir la boutique</a>
          @else
            <div class="table-responsive">
              <table class="table">
                <thead>
                  <tr>
                    <th>Commande</th>
                    <th>Date</th>
                    <th>Statut</th>
                    <th>Total</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  @foreach ($recentOrders as $order)
                    <tr>
                      <td>{{ $order->order_number }}</td>
                      <td>{{ $order->created_at->format('d/m/Y') }}</td>
                      <td>{{ $order->status->label() }}</td>
                      <td>{{ $currencyService->format($order->total, $order->currency) }}</td>
                      <td>
                        <a href="{{ route('account.orders.show', $order) }}" class="btn btn-fill-out btn-sm">Voir</a>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @endif
        </div>
      </div>
    </div>
  </x-shopwise-account-shell>
</div>
