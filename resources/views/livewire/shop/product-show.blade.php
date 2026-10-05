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
  $averageRating = $product->averageRating();
  $reviewCount = $reviews->count();
  $ratingWidth = $reviewCount > 0 ? ($averageRating / 5) * 100 : 0;
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
                  <span class="rating_num">
                    @if ($reviewCount > 0)
                      {{ $averageRating }}/5 ({{ $reviewCount }} avis)
                    @else
                      Aucun avis pour l'instant
                    @endif
                  </span>
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
              @php($whatsappOrderUrl = app(\App\Services\SiteSettingsService::class)->whatsappUrl('Bonjour Chez Lia 👋 Je souhaite commander « ' . $product->name . ' » (' . route('products.show', $product) . ').'))
              @if ($whatsappOrderUrl)
                <a href="{{ $whatsappOrderUrl }}" target="_blank" rel="noopener" class="btn btn-whatsapp-order mt-3">
                  <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.5 2 12.04 2Zm5.8 14.16c-.25.69-1.44 1.32-1.98 1.37-.51.05-.99.24-3.33-.69-2.81-1.11-4.6-3.98-4.74-4.17-.14-.18-1.13-1.5-1.13-2.87s.72-2.03.97-2.31c.25-.28.55-.35.74-.35l.53.01c.17 0 .4-.06.62.48.25.6.85 2.07.92 2.22.07.15.12.32.02.51-.1.19-.15.31-.29.48-.15.17-.31.38-.44.51-.15.15-.3.31-.13.6.17.29.77 1.27 1.65 2.06 1.13 1.01 2.09 1.32 2.38 1.47.29.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.29.39-.24.66-.15.27.1 1.72.81 2.02.96.29.15.49.22.56.34.07.12.07.71-.18 1.4Z"/></svg>
                  Commander sur WhatsApp
                </a>
                <style>
                  .btn-whatsapp-order { display:inline-flex; align-items:center; gap:8px; background:#25D366; color:#fff !important; border-radius:4px; padding:10px 18px; font-weight:600; }
                  .btn-whatsapp-order:hover { background:#1ebe57; color:#fff; }
                </style>
              @endif
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
              @if ($rangeProducts->isNotEmpty())
                <p class="text-muted small mb-0">
                  Vendable seul ou en gamme — {{ $rangeProducts->count() }} produit{{ $rangeProducts->count() > 1 ? 's' : '' }} associé{{ $rangeProducts->count() > 1 ? 's' : '' }}.
                </p>
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
                <li class="nav-item">
                  <a class="nav-link" id="Reviews-tab" data-bs-toggle="tab" href="#Reviews" role="tab" aria-controls="Reviews" aria-selected="false">Avis ({{ $reviewCount }})</a>
                </li>
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
                <div class="tab-pane fade" id="Reviews" role="tabpanel" aria-labelledby="Reviews-tab">
                  @forelse ($reviews as $review)
                    <div class="review-item mb-4 pb-4" style="border-bottom: 1px solid #eee;">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <strong>{{ $review->user->name ?? 'Client' }}</strong>
                          @if ($review->is_verified_purchase)
                            <span class="badge bg-success ms-2" style="font-size: 11px;">Achat vérifié</span>
                          @endif
                          <div class="rating mt-1">
                            <div class="product_rate" style="width:{{ ($review->rating / 5) * 100 }}%"></div>
                          </div>
                        </div>
                        <small class="text-muted">{{ $review->created_at->format('d/m/Y') }}</small>
                      </div>
                      @if ($review->title)
                        <p class="mb-1 mt-2"><strong>{{ $review->title }}</strong></p>
                      @endif
                      @if ($review->comment)
                        <p class="mb-0 mt-1">{{ $review->comment }}</p>
                      @endif
                    </div>
                  @empty
                    <p>Aucun avis pour l'instant. Soyez le premier à donner votre avis !</p>
                  @endforelse

                  <hr class="my-4">

                  @auth
                    @if ($userHasReviewed)
                      <p class="text-muted">Vous avez déjà laissé un avis sur ce produit. Merci !</p>
                    @else
                      <h5 class="mb-3">Laisser un avis</h5>
                      <form wire:submit="submitReview" novalidate>
                        <div class="form-group mb-3">
                          <label class="d-block mb-1">Note</label>
                          <select wire:model="reviewRating" class="form-control" style="max-width: 160px;">
                            <option value="5">5 — Excellent</option>
                            <option value="4">4 — Très bien</option>
                            <option value="3">3 — Correct</option>
                            <option value="2">2 — Décevant</option>
                            <option value="1">1 — Mauvais</option>
                          </select>
                          @error('reviewRating') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group mb-3">
                          <input
                            type="text"
                            wire:model="reviewTitle"
                            class="form-control @error('reviewTitle') is-invalid @enderror"
                            placeholder="Titre (optionnel)"
                          >
                          @error('reviewTitle') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <div class="form-group mb-3">
                          <textarea
                            wire:model="reviewComment"
                            rows="4"
                            class="form-control @error('reviewComment') is-invalid @enderror"
                            placeholder="Votre avis sur ce produit…"
                          ></textarea>
                          @error('reviewComment') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        </div>
                        <button type="submit" class="btn btn-fill-out" wire:loading.attr="disabled">
                          <span wire:loading.remove wire:target="submitReview">Envoyer mon avis</span>
                          <span wire:loading wire:target="submitReview">Envoi…</span>
                        </button>
                        <p class="text-muted small mt-2">Votre avis sera publié après validation par notre équipe.</p>
                      </form>
                    @endif
                  @else
                    <p>
                      <a href="{{ route('account.login') }}" wire:navigate>Connectez-vous</a>
                      pour laisser un avis sur ce produit.
                    </p>
                  @endauth
                </div>
              </div>
            </div>
          </div>
        </div>

        @if ($rangeProducts->isNotEmpty())
          <div class="row">
            <div class="col-12">
              <div class="large_divider clearfix"></div>
            </div>
          </div>
          <div class="row">
            <div class="col-12">
              <div class="heading_s1">
                <h3>Cette gamme</h3>
              </div>
              <p class="text-muted mb-4">
                Ce soin se vend à l'unité. Ajoutez aussi les produits liés pour une routine complète.
              </p>
              <div class="row shop_container">
                @foreach ($rangeProducts as $rangeProduct)
                  <div class="col-md-3 col-6" wire:key="range-product-{{ $rangeProduct->id }}">
                    <x-shopwise-product-item
                      :product="$rangeProduct"
                      :favorite-ids="$favoriteIds"
                      :cart-added-product-id="$cartAddedProductId"
                    />
                  </div>
                @endforeach
              </div>
              <div class="mt-3">
                <x-lw-action
                  action="addRangeToCart"
                  class="btn btn-fill-out"
                  :prevent="false"
                  loading-label="Ajout de la gamme..."
                  loader-size="md"
                >
                  Ajouter toute la gamme au panier
                </x-lw-action>
              </div>
            </div>
          </div>
        @endif
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
