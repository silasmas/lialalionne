@php
  $galleryImages = $images->isNotEmpty()
    ? $images
    : collect([(object) [
      'path' => null,
      'url' => null,
      'alt_text' => $product->name,
      'fallback' => $sw('images/product_img1.jpg'),
      'thumb' => $sw('images/product_small_img1.jpg'),
      'zoom' => $sw('images/product_zoom_img1.jpg'),
    ]]);

  $primaryImage = $galleryImages->first();
  $mainImageUrl = $primaryImage->url
    ?? $primaryImage->fallback
    ?? $sw('images/product_img1.jpg');
  $mainZoomUrl = $primaryImage->zoom ?? $mainImageUrl;

  $discountPercent = null;

  if ($product->hasDiscount() && (float) $product->compare_at_price > 0) {
    $discountPercent = (int) round((1 - ((float) $product->price / (float) $product->compare_at_price)) * 100);
  }

  $isFavorite = in_array($product->id, $favoriteIds, true);
  $ratingWidth = 60 + (($product->id * 17) % 35);
  $reviewCount = 5 + (($product->id * 3) % 40);
@endphp

<div>
  <x-shopwise-breadcrumb
    :title="$product->name"
    :items="[
      ['label' => 'Boutique', 'url' => route('shop.catalog')],
      ['label' => $product->category->name, 'url' => route('shop.catalog', ['categorie' => $product->category_id])],
      ['label' => $product->name, 'url' => route('products.show', $product)],
    ]"
  />

  <div class="main_content">
    <div class="section">
      <div class="container">
        <div class="row">
          <div class="col-lg-6 col-md-6 mb-4 mb-md-0">
            <div class="product-image vertical_gallery" wire:ignore>
              @if ($galleryImages->count() > 1)
                <div
                  id="pr_item_gallery"
                  class="product_gallery_item slick_slider"
                  data-vertical="true"
                  data-vertical-swiping="true"
                  data-slides-to-show="5"
                  data-slides-to-scroll="1"
                  data-infinite="false"
                >
                  @foreach ($galleryImages as $index => $image)
                    @php
                      $imageUrl = $image->url
                        ?? $image->fallback
                        ?? $sw('images/product_img1.jpg');
                      $thumbUrl = $image->url ? $imageUrl : ($image->thumb ?? $imageUrl);
                      $zoomUrl = $image->url ? $imageUrl : ($image->zoom ?? $imageUrl);
                    @endphp
                    <div class="item">
                      <a
                        href="#"
                        class="product_gallery_item {{ $index === 0 ? 'active' : '' }}"
                        data-image="{{ $imageUrl }}"
                        data-zoom-image="{{ $zoomUrl }}"
                      >
                        <img src="{{ $thumbUrl }}" alt="{{ $image->alt_text ?? $product->name }}">
                      </a>
                    </div>
                  @endforeach
                </div>
              @endif
              <div class="product_img_box">
                @if ($discountPercent)
                  <x-discount-ribbon :percent="$discountPercent" />
                @endif
                <img
                  id="product_img"
                  src="{{ $mainImageUrl }}"
                  data-zoom-image="{{ $mainZoomUrl }}"
                  alt="{{ $product->name }}"
                >
                <a href="#" class="product_img_zoom" title="Zoom">
                  <span class="linearicons-zoom-in"></span>
                </a>
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
                  <span class="price">{{ $product->formatPrice($this->currentPrice) }}</span>
                  @if ($product->hasDiscount() && !$this->selectedVariant)
                    <del>{{ $product->formatPrice($product->compare_at_price) }}</del>
                  @endif
                </div>
                <div class="rating_wrap">
                  <div class="rating">
                    <div class="product_rate" style="width:{{ $ratingWidth }}%"></div>
                  </div>
                  <span class="rating_num">({{ $reviewCount }})</span>
                </div>
                @if ($product->short_description)
                  <div class="pr_desc">
                    <p>{{ $product->short_description }}</p>
                  </div>
                @endif
                <div class="product_sort_info">
                  <ul>
                    <li><i class="linearicons-shield-check"></i> Produits authentiques Lialalionne</li>
                    <li><i class="linearicons-sync"></i> Politique de retour sous 14 jours</li>
                    <li><i class="linearicons-bag-dollar"></i> Mobile Money et carte bancaire</li>
                  </ul>
                </div>
                @if ($product->variants->isNotEmpty())
                  <div class="pr_switch_wrap">
                    <span class="switch_lable">Format</span>
                    <div class="product_size_switch">
                      @foreach ($product->variants as $variant)
                        <span
                          role="button"
                          wire:click="selectVariant({{ $variant->id }})"
                          class="{{ $selectedVariantId === $variant->id ? 'active' : '' }}"
                          title="{{ $variant->name }}"
                        >
                          {{ \Illuminate\Support\Str::limit($variant->name, 8, '') }}
                        </span>
                      @endforeach
                    </div>
                  </div>
                @endif
              </div>
              <hr>
              <div class="cart_extra">
                <div class="cart-product-quantity">
                  <div class="quantity">
                    <input type="button" value="-" class="minus" wire:click="decrementQuantity" wire:loading.attr="disabled" wire:target="decrementQuantity" wire:loading.class="lw-action--loading">
                    <input type="text" name="quantity" value="{{ $quantity }}" title="Qté" class="qty" size="4" readonly>
                    <input type="button" value="+" class="plus" wire:click="incrementQuantity" wire:loading.attr="disabled" wire:target="incrementQuantity" wire:loading.class="lw-action--loading">
                  </div>
                </div>
                <div class="cart_btn">
                  <x-lw-action
                    action="addToCart"
                    class="btn btn-fill-out btn-addtocart"
                    :prevent="false"
                    loading-label="Ajout..."
                    loader-size="md"
                    :disabled="!$this->isAvailable"
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
                    title="{{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
                  >
                    <i class="icon-heart"></i>
                  </x-lw-action>
                </div>
              </div>
              <hr>
              @if ($cartMessage)
                <div class="alert alert-success py-2">
                  {{ $cartMessage }}
                  <a href="{{ route('shop.cart') }}" class="ms-1">Voir le panier</a>
                </div>
              @endif
              @error('cart')
                <div class="alert alert-danger py-2">{{ $message }}</div>
              @enderror
              @if ($this->isAvailable)
                <p class="text-success mb-3">En stock — livraison sous 3 à 5 jours ouvrés</p>
              @else
                <p class="text-danger mb-3">Produit momentanément indisponible</p>
              @endif
              <ul class="product-meta">
                <li>SKU: <span>{{ $product->sku }}</span></li>
                <li>
                  Catégorie:
                  <a href="{{ route('shop.catalog', ['categorie' => $product->category_id]) }}">
                    {{ $product->category->name }}
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="large_divider clearfix"></div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="tab-style3">
              <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="Description-tab" data-bs-toggle="tab" href="#Description" role="tab" aria-controls="Description" aria-selected="true">Description</a>
                </li>
                @if ($product->ingredients || $product->usage_tips)
                  <li class="nav-item">
                    <a class="nav-link" id="Additional-info-tab" data-bs-toggle="tab" href="#Additional-info" role="tab" aria-controls="Additional-info" aria-selected="false">Informations</a>
                  </li>
                @endif
              </ul>
              <div class="tab-content shop_info_tab">
                <div class="tab-pane fade show active" id="Description" role="tabpanel" aria-labelledby="Description-tab">
                  @if ($product->description)
                    {!! nl2br(e($product->description)) !!}
                  @else
                    <p>{{ $product->short_description ?? 'Aucune description disponible pour ce produit.' }}</p>
                  @endif
                </div>
                @if ($product->ingredients || $product->usage_tips)
                  <div class="tab-pane fade" id="Additional-info" role="tabpanel" aria-labelledby="Additional-info-tab">
                    <table class="table table-bordered">
                      @if ($product->ingredients)
                        <tr>
                          <td>Ingrédients</td>
                          <td>{!! nl2br(e($product->ingredients)) !!}</td>
                        </tr>
                      @endif
                      @if ($product->usage_tips)
                        <tr>
                          <td>Conseils d'utilisation</td>
                          <td>{!! nl2br(e($product->usage_tips)) !!}</td>
                        </tr>
                      @endif
                    </table>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  @if ($this->quickViewProduct)
    <x-shopwise-quick-view-modal :product="$this->quickViewProduct" />
  @endif

  @if ($showCompareModal && $this->compareProducts->isNotEmpty())
    <x-shopwise-compare-modal :products="$this->compareProducts" />
  @endif
</div>
