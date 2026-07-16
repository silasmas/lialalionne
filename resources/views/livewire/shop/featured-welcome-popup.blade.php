@if ($products->isNotEmpty())
  {{-- Modale d'accueil inspirée du template Shopwise (subscribe_popup), personnalisée Lialalionne --}}
  <div
    class="modal fade subscribe_popup featured-welcome-popup"
    id="featured-welcome-popup"
    tabindex="-1"
    role="dialog"
    aria-hidden="true"
    data-bs-backdrop="true"
    wire:ignore
  >
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-body">
          <button type="button" class="close" data-bs-dismiss="modal" aria-label="Fermer">
            <span aria-hidden="true"><i class="ion-ios-close-empty"></i></span>
          </button>

          <div
            id="featuredWelcomeCarousel"
            class="carousel slide featured-welcome-carousel"
            data-bs-ride="carousel"
            data-bs-interval="4500"
          >
            <div class="carousel-indicators">
              @foreach ($products as $index => $product)
                <button
                  type="button"
                  data-bs-target="#featuredWelcomeCarousel"
                  data-bs-slide-to="{{ $index }}"
                  class="{{ $index === 0 ? 'active' : '' }}"
                  aria-current="{{ $index === 0 ? 'true' : 'false' }}"
                  aria-label="Slide {{ $index + 1 }}"
                ></button>
              @endforeach
            </div>

            <div class="carousel-inner">
              @foreach ($products as $index => $product)
                @php
                  $imageUrl = $product->primaryImageUrl();
                  $discountPercent = null;

                  if ($product->hasDiscount() && (float) $product->compare_at_price > 0) {
                    $discountPercent = (int) round((1 - ((float) $product->price / (float) $product->compare_at_price)) * 100);
                  }
                @endphp
                <div class="carousel-item {{ $index === 0 ? 'active' : '' }}">
                  <div class="row g-0 featured-welcome-slide">
                    <div class="col-sm-5">
                      <div class="featured-welcome-media">
                        @if ($discountPercent)
                          <x-discount-ribbon :percent="$discountPercent" />
                        @endif
                        <img
                          src="{{ $imageUrl }}"
                          alt="{{ $product->name }}"
                          class="featured-welcome-image"
                        >
                      </div>
                    </div>
                    <div class="col-sm-7">
                      <div class="popup_content featured-welcome-content">
                        <div class="popup-text">
                          <p class="featured-welcome-eyebrow text-uppercase mb-2">Sélection vedette</p>
                          <div class="heading_s1">
                            <h4>{{ $product->name }}</h4>
                          </div>
                          @if ($product->category)
                            <p class="featured-welcome-category mb-2">{{ $product->category->name }}</p>
                          @endif
                          @if ($product->short_description)
                            <p class="mb-3">{{ \Illuminate\Support\Str::limit($product->short_description, 110) }}</p>
                          @endif
                          <div class="featured-welcome-price mb-4">
                            <span class="price">{{ $product->formatPrice() }}</span>
                            @if ($product->hasDiscount())
                              <del>{{ $product->formatPrice($product->compare_at_price) }}</del>
                            @endif
                          </div>
                        </div>
                        <div class="form-group mb-3">
                          <a
                            href="{{ route('products.show', $product) }}"
                            class="btn btn-fill-out btn-block text-uppercase"
                          >
                            Découvrir
                          </a>
                        </div>
                        <a href="{{ route('shop.catalog') }}" class="btn btn-border-fill btn-block text-uppercase mb-3">
                          Voir la boutique
                        </a>
                        <div class="chek-form">
                          <div class="custome-checkbox">
                            <input
                              class="form-check-input"
                              type="checkbox"
                              id="featured-popup-hide-{{ $index }}"
                              data-featured-popup-hide
                            >
                            <label class="form-check-label" for="featured-popup-hide-{{ $index }}">
                              <span>Ne plus afficher cette fenêtre</span>
                            </label>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>

            @if ($products->count() > 1)
              <button class="carousel-control-prev" type="button" data-bs-target="#featuredWelcomeCarousel" data-bs-slide="prev">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Précédent</span>
              </button>
              <button class="carousel-control-next" type="button" data-bs-target="#featuredWelcomeCarousel" data-bs-slide="next">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Suivant</span>
              </button>
            @endif
          </div>
        </div>
      </div>
    </div>
  </div>
@endif
