@if ($product)
  <div class="shopwise-mfp-overlay" wire:click="closeQuickView"></div>

  <div class="mfp-wrap shopwise-mfp-open mfp-close-btn-in" tabindex="-1">
    <div class="mfp-container mfp-inline-holder">
      <div class="mfp-content">
        <div class="white-popup mfp-with-anim ajax_quick_view_popup">
          <button type="button" class="mfp-close" wire:click="closeQuickView" aria-label="Fermer">
            <i class="ion-ios-close-empty"></i>
          </button>

          <div class="ajax_quick_view">
            <div class="row">
              <div class="col-lg-6 col-md-6 mb-4 mb-md-0">
                <div class="product-image">
                  <div class="product_img_box">
                    <img
                      id="product_img"
                      src="{{ $product->primaryImageUrl() ?? asset('shopwise/assets/images/product_img1.jpg') }}"
                      alt="{{ $product->name }}"
                    />
                  </div>
                </div>
              </div>

              <div class="col-lg-6 col-md-6">
                <div class="pr_detail">
                  <div class="product_description">
                    <h4 class="product_title">
                      <a href="{{ route('products.show', $product) }}">{{ $product->name }}</a>
                    </h4>
                    <div class="product_price">
                      <span class="price">{{ $product->formatPrice() }}</span>
                      @if ($product->hasDiscount())
                        <del>{{ $product->formatPrice($product->compare_at_price) }}</del>
                        <div class="on_sale"><span>Promo</span></div>
                      @endif
                    </div>
                    @if ($product->short_description)
                      <div class="pr_desc">
                        <p>{{ $product->short_description }}</p>
                      </div>
                    @endif
                    <div class="product_sort_info">
                      <ul>
                        <li><i class="linearicons-shield-check"></i> Produits authentiques Lialalionne</li>
                        <li><i class="linearicons-sync"></i> Politique de retour 30 jours</li>
                        <li><i class="linearicons-bag-dollar"></i> Mobile Money & carte bancaire</li>
                      </ul>
                    </div>
                  </div>

                  <hr />

                  <div class="cart_extra">
                    <div class="cart_btn">
                      <x-lw-action
                        :action="'addProductToCart(' . $product->id . ')'"
                        class="btn btn-fill-out btn-addtocart"
                        :prevent="false"
                        loading-label="Ajout..."
                        loader-size="md"
                      >
                        <i class="icon-basket-loaded"></i> Ajouter au panier
                      </x-lw-action>
                      <x-lw-action
                        :action="'addProductToCompare(' . $product->id . ')'"
                        tag="a"
                        class="add_compare"
                        title="Comparer"
                      >
                        <i class="icon-shuffle"></i>
                      </x-lw-action>
                      <x-lw-action
                        :action="'toggleProductFavorite(' . $product->id . ')'"
                        tag="a"
                        class="add_wishlist"
                        title="Favoris"
                      >
                        <i class="icon-heart"></i>
                      </x-lw-action>
                    </div>
                  </div>

                  <hr />

                  <ul class="product-meta">
                    <li>Réf. : {{ $product->sku }}</li>
                    @if ($product->category)
                      <li>Catégorie : <a href="{{ route('shop.catalog', ['categorie' => $product->category_id]) }}">{{ $product->category->name }}</a></li>
                    @endif
                  </ul>

                  <a href="{{ route('products.show', $product) }}" class="btn btn-fill-line rounded-0 mt-3">
                    Voir le détail complet
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endif
