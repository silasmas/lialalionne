@props([
  'products',
  'favoriteIds' => [],
  'cartAddedProductId' => null,
  'markFirstAsNew' => false,
  'gridKey' => 'exclusive',
])

<div class="row shop_container">
  @forelse ($products as $product)
    <div class="col-lg-3 col-md-4 col-6" wire:key="{{ $gridKey }}-{{ $product->id }}">
      <x-shopwise-product-item
        :product="$product"
        :favorite-ids="$favoriteIds"
        :cart-added-product-id="$cartAddedProductId"
        :show-new-badge="$markFirstAsNew && $loop->first"
      />
    </div>
  @empty
    <div class="col-12">
      <p class="text-center text-muted py-4">Aucun produit dans cette rubrique pour le moment.</p>
    </div>
  @endforelse
</div>
