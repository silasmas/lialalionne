@php
  use App\Support\ShopwiseAssets;
@endphp

<li class="dropdown cart_dropdown">
  <a
    class="nav-link cart_trigger"
    href="{{ route('shop.cart') }}"
    aria-label="Panier ({{ $count }} article{{ $count > 1 ? 's' : '' }})"
  >
    <i class="linearicons-cart"></i>
    @if ($count > 0)
      <span class="cart_count">{{ $count > 99 ? '99+' : $count }}</span>
    @endif
  </a>

  <div class="cart_box dropdown-menu dropdown-menu-end">
    @if ($items->isEmpty())
      <div class="p-3 text-center text-muted">
        Votre panier est vide.
      </div>
    @else
      <ul class="cart_list">
        @foreach ($items as $item)
          @php
            $imageUrl = $item->product
              ? ShopwiseAssets::productImageUrl($item->product_id)
              : asset('shopwise/assets/images/product_img1.jpg');
          @endphp
          <li wire:key="mini-cart-item-{{ $item->id }}">
            <x-lw-action
              :action="'removeItem(' . $item->id . ')'"
              class="item_remove border-0 bg-transparent p-0"
              :stop="true"
              :aria-label="'Retirer ' . $item->product?->name"
            >
              <i class="ion-close"></i>
            </x-lw-action>
            <a href="{{ route('products.show', $item->product) }}">
              <img src="{{ $imageUrl }}" alt="{{ $item->product?->name }}">
              {{ $item->product?->name }}
            </a>
            <span class="cart_quantity">
              {{ $item->quantity }} x
              <span class="cart_amount">{{ $item->product?->formatPrice($item->unit_price) }}</span>
            </span>
          </li>
        @endforeach
      </ul>

      <div class="cart_footer">
        <p class="cart_total">
          <strong>Sous-total :</strong>
          <span class="cart_price">{{ $subtotalFormatted }}</span>
        </p>
        <p class="cart_buttons d-flex flex-row flex-nowrap gap-2 justify-content-center">
          <a href="{{ route('shop.cart') }}" class="btn btn-fill-line rounded-0 view-cart flex-fill">Voir le panier</a>
          <a href="{{ route('shop.checkout') }}" class="btn btn-fill-out rounded-0 checkout flex-fill">Commander</a>
        </p>
      </div>
    @endif
  </div>
</li>
