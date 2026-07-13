@if ($products->isNotEmpty())
  <div class="shopwise-mfp-overlay" wire:click="closeCompareModal"></div>

  <div class="mfp-wrap shopwise-mfp-open mfp-close-btn-in mfp-ajax-holder" tabindex="-1">
    <div class="mfp-container">
      <div class="mfp-content">
        <div class="white-popup mfp-with-anim">
          <button type="button" class="mfp-close" wire:click="closeCompareModal" aria-label="Fermer">
            <i class="ion-ios-close-empty"></i>
          </button>

          <div class="compare_box">
            <div class="table-responsive">
              <table class="table table-bordered text-center">
                <tbody>
                  <tr class="pr_image">
                    <td class="row_title">Image produit</td>
                    @foreach ($products as $product)
                      <td class="row_img" wire:key="modal-compare-image-{{ $product->id }}">
                        <img
                          src="{{ $product->primaryImageUrl() ?? asset('shopwise/assets/images/product_img1.jpg') }}"
                          alt="{{ $product->name }}"
                        >
                      </td>
                    @endforeach
                  </tr>

                  <tr class="pr_title">
                    <td class="row_title">Nom</td>
                    @foreach ($products as $product)
                      <td class="product_name" wire:key="modal-compare-name-{{ $product->id }}">
                        <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                      </td>
                    @endforeach
                  </tr>

                  <tr class="pr_price">
                    <td class="row_title">Prix</td>
                    @foreach ($products as $product)
                      <td class="product_price" wire:key="modal-compare-price-{{ $product->id }}">
                        <span class="price">{{ $product->formatPrice() }}</span>
                      </td>
                    @endforeach
                  </tr>

                  <tr class="pr_add_to_cart">
                    <td class="row_title">Panier</td>
                    @foreach ($products as $product)
                      <td class="row_btn" wire:key="modal-compare-cart-{{ $product->id }}">
                        <x-lw-action
                          :action="'addProductToCart(' . $product->id . ')'"
                          class="btn btn-fill-out"
                          :prevent="false"
                          loading-label="Ajout..."
                        >
                          <i class="icon-basket-loaded"></i> Ajouter
                        </x-lw-action>
                      </td>
                    @endforeach
                  </tr>

                  <tr class="description">
                    <td class="row_title">Description</td>
                    @foreach ($products as $product)
                      <td class="row_text" wire:key="modal-compare-desc-{{ $product->id }}">
                        <p>{{ $product->short_description ?: '—' }}</p>
                      </td>
                    @endforeach
                  </tr>

                  <tr>
                    <td class="row_title">Catégorie</td>
                    @foreach ($products as $product)
                      <td wire:key="modal-compare-cat-{{ $product->id }}">
                        {{ $product->category?->name ?? '—' }}
                      </td>
                    @endforeach
                  </tr>

                  <tr>
                    <td class="row_title">Retirer</td>
                    @foreach ($products as $product)
                      <td wire:key="modal-compare-remove-{{ $product->id }}">
                        <x-lw-action
                          :action="'removeFromCompareModal(' . $product->id . ')'"
                          class="btn btn-link text-danger p-0"
                          :prevent="false"
                          loading-label="..."
                        >
                          <i class="ion-close"></i> Retirer
                        </x-lw-action>
                      </td>
                    @endforeach
                  </tr>
                </tbody>
              </table>
            </div>
          </div>

          <div class="text-center mt-3">
            <a href="{{ route('shop.compare') }}" class="btn btn-fill-line rounded-0">Page comparer complète</a>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif
