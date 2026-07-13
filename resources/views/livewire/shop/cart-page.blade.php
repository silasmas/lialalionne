@php
  use App\Support\ShopwiseAssets;
@endphp

<div>
  <x-shopwise-breadcrumb
    title="Panier"
    :items="[['label' => 'Panier', 'url' => route('shop.cart')]]"
  />

  <div class="main_content">
    <div class="section">
      <div class="container">
        @error('cart')
          <div class="alert alert-danger">{{ $message }}</div>
        @enderror

        @if ($items->isEmpty())
          <div class="text-center py-5">
            <p class="text-muted mb-4">Votre panier est vide.</p>
            <a href="{{ route('shop.catalog') }}" class="btn btn-fill-out">Découvrir la boutique</a>
          </div>
        @else
          <div class="row">
            <div class="col-12">
              <div class="table-responsive shop_cart_table">
                <table class="table">
                  <thead>
                    <tr>
                      <th class="product-thumbnail">&nbsp;</th>
                      <th class="product-name">Produit</th>
                      <th class="product-price">Prix</th>
                      <th class="product-quantity">Quantité</th>
                      <th class="product-subtotal">Total</th>
                      <th class="product-remove">Retirer</th>
                    </tr>
                  </thead>
                  <tbody>
                    @foreach ($items as $item)
                      <tr wire:key="cart-item-{{ $item->id }}">
                        <td class="product-thumbnail">
                          <a href="{{ route('products.show', $item->product) }}">
                            <img
                              src="{{ ShopwiseAssets::productImageUrl($item->product_id) }}"
                              alt="{{ $item->product->name }}"
                            >
                          </a>
                        </td>
                        <td class="product-name" data-title="Produit">
                          <a href="{{ route('products.show', $item->product) }}">{{ $item->product->name }}</a>
                          @if ($item->variant)
                            <br><small class="text-muted">{{ $item->variant->name }}</small>
                          @endif
                        </td>
                        <td class="product-price" data-title="Prix">
                          {{ $item->product->formatPrice($item->unit_price) }}
                        </td>
                        <td class="product-quantity" data-title="Quantité">
                          <div class="quantity">
                            <x-lw-action
                              :action="'updateQuantity(' . $item->id . ', ' . max(1, $item->quantity - 1) . ')'"
                              class="minus"
                              loading-label="-"
                            >
                              -
                            </x-lw-action>
                            <input type="text" value="{{ $item->quantity }}" title="Qté" class="qty" size="4" readonly>
                            <x-lw-action
                              :action="'updateQuantity(' . $item->id . ', ' . ($item->quantity + 1) . ')'"
                              class="plus"
                              loading-label="+"
                            >
                              +
                            </x-lw-action>
                          </div>
                        </td>
                        <td class="product-subtotal" data-title="Total">
                          {{ $item->product->formatPrice($item->lineTotal()) }}
                        </td>
                        <td class="product-remove" data-title="Retirer">
                          <x-lw-action
                            :action="'removeItem(' . $item->id . ')'"
                            tag="a"
                            confirm="Retirer cet article du panier ?"
                            aria-label="Retirer"
                          >
                            <i class="ti-close"></i>
                          </x-lw-action>
                        </td>
                      </tr>
                    @endforeach
                  </tbody>
                  <tfoot>
                    <tr>
                      <td colspan="6" class="px-0">
                        <div class="row g-0 align-items-center">
                          <div class="col-lg-4 col-md-6 mb-3 mb-md-0">
                            <a href="{{ route('shop.catalog') }}" class="btn btn-line-fill btn-sm">
                              Continuer mes achats
                            </a>
                          </div>
                          <div class="col-lg-8 col-md-6 text-start text-md-end">
                            <x-lw-action
                              action="clearCart"
                              confirm="Vider entièrement le panier ?"
                              class="btn btn-line-fill btn-sm"
                              loading-label="Vidage..."
                            >
                              Vider le panier
                            </x-lw-action>
                          </div>
                        </div>
                      </td>
                    </tr>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-12">
              <div class="medium_divider"></div>
              <div class="divider center_icon"><i class="ti-shopping-cart-full"></i></div>
              <div class="medium_divider"></div>
            </div>
          </div>

          <div class="row justify-content-end">
            <div class="col-md-6">
              <div class="border p-3 p-md-4">
                <div class="heading_s1 mb-3">
                  <h6>Total panier</h6>
                </div>
                <div class="table-responsive">
                  <table class="table">
                    <tbody>
                      <tr>
                        <td class="cart_total_label">Sous-total</td>
                        <td class="cart_total_amount">{{ $currencyService->formatFromEur($subtotal) }}</td>
                      </tr>
                      <tr>
                        <td class="cart_total_label">Livraison</td>
                        <td class="cart_total_amount">Calculée à l'étape suivante</td>
                      </tr>
                      <tr>
                        <td class="cart_total_label">Total estimé</td>
                        <td class="cart_total_amount"><strong>{{ $currencyService->formatFromEur($subtotal) }}</strong></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
                <a href="{{ route('shop.checkout') }}" class="btn btn-fill-out">Passer commande</a>
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>
  </div>
</div>
