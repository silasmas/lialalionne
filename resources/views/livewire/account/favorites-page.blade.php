<div>
  <x-shopwise-account-shell
    title="Mes favoris"
    :breadcrumb-items="[['label' => 'Mon compte', 'url' => route('account.dashboard')], ['label' => 'Favoris', 'url' => route('account.favorites')]]"
  >
    <div class="card">
      <div class="card-header">
        <h3>Favoris</h3>
      </div>
      <div class="card-body">
        @if ($products->isEmpty())
          <p class="text-center mb-0">Aucun favori pour le moment.</p>
          <div class="text-center mt-3">
            <a href="{{ route('shop.catalog') }}" class="btn btn-fill-out btn-sm">Explorer la boutique</a>
          </div>
        @else
          <div class="row shop_container grid">
            @foreach ($products as $product)
              <div class="col-md-4 col-6" wire:key="favorite-product-{{ $product->id }}">
                <x-shopwise-product-item
                  :product="$product"
                  :favorite-ids="$favoriteIds"
                  :cart-added-product-id="$cartAddedProductId"
                />
              </div>
            @endforeach
          </div>
        @endif
      </div>
    </div>

    @if ($this->quickViewProduct)
      <x-shopwise-quick-view-modal :product="$this->quickViewProduct" />
    @endif

    @if ($showCompareModal && $this->compareProducts->isNotEmpty())
      <x-shopwise-compare-modal :products="$this->compareProducts" />
    @endif
  </x-shopwise-account-shell>
</div>
