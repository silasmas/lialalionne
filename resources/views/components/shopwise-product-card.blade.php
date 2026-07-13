@props([
  'product',
  'favoriteIds' => [],
  'cartAddedProductId' => null,
])

<div class="col-lg-3 col-md-4 col-6">
  <x-shopwise-product-item
    :product="$product"
    :favorite-ids="$favoriteIds"
    :cart-added-product-id="$cartAddedProductId"
  />
</div>
