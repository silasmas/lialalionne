@props([
  'products',
  'favoriteIds' => [],
  'cartAddedProductId' => null,
  'templateImages' => [],
  'markFirstAsNew' => false,
  'gridKey' => 'exclusive',
])

<div class="row shop_container">
  @forelse ($products as $product)
    @php
      $imageUrl = $templateImages[$loop->index % max(count($templateImages), 1)] ?? asset('shopwise/assets/images/product_img1.jpg');
    @endphp
    <div class="col-lg-3 col-md-4 col-6" wire:key="{{ $gridKey }}-{{ $product->id }}">
      <x-shopwise-product-item
        :product="$product"
        :favorite-ids="$favoriteIds"
        :cart-added-product-id="$cartAddedProductId"
        :display-image-url="$imageUrl"
        :show-new-badge="$markFirstAsNew && $loop->first"
      />
    </div>
  @empty
    <div class="col-12">
      <p class="text-center text-muted py-4">Aucun produit dans cette rubrique pour le moment.</p>
    </div>
  @endforelse
</div>
