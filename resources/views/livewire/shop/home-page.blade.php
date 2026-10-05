@php
  $sw = fn (string $path): string => asset('shopwise/assets/' . ltrim($path, '/'));

  $testimonials = [
    [
      'quote' => 'Les produits Lialalionne ont transformé ma routine de soins. Ma peau est plus douce et éclatante depuis que j\'utilise leur gamme.',
      'name' => 'Marie Kabila',
      'role' => 'Cliente fidèle',
      'image' => $sw('images/user_img1.jpg'),
    ],
    [
      'quote' => 'Livraison rapide à Kinshasa et paiement Mobile Money très pratique. Je recommande vivement cette boutique.',
      'name' => 'Grace Mbuyi',
      'role' => 'Kinshasa',
      'image' => $sw('images/user_img2.jpg'),
    ],
    [
      'quote' => 'Qualité premium à prix accessibles. Le gel nettoyant est devenu mon indispensable quotidien.',
      'name' => 'Amina N\'senga',
      'role' => 'Esthéticienne',
      'image' => $sw('images/user_img3.jpg'),
    ],
    [
      'quote' => 'Service client réactif et produits naturels authentiques. Lialalionne est ma référence pour les soins corporels.',
      'name' => 'Jean-Paul M.',
      'role' => 'Client régulier',
      'image' => $sw('images/user_img4.jpg'),
    ],
  ];
@endphp

<div>
  @if ($homeSlides->isNotEmpty())
  <div class="banner_section slide_medium shop_banner_slider staggered-animation-wrap">
    <div id="carouselExampleControls" class="carousel slide carousel-fade light_arrow" data-bs-ride="carousel">
      <div class="carousel-inner">
        @foreach ($homeSlides as $index => $slide)
          @php
            $isVideo = $slide->isVideo();
            $mediaUrl = $slide->mediaUrl();
            $buttonHref = $slide->buttonHref();
            $itemClasses = $index === 0 ? 'carousel-item active' : 'carousel-item';
            $itemClasses .= $isVideo ? ' banner_slide_video' : ' background_bg';
          @endphp
          <div
            class="{{ $itemClasses }}"
            @if (!$isVideo && $mediaUrl) data-img-src="{{ $mediaUrl }}" @endif
            data-bs-interval="{{ $isVideo ? 140000 : 6000 }}"
          >
            @if ($isVideo && $mediaUrl)
              <video
                class="banner_slide_video_el"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                @if ($slide->posterUrl()) poster="{{ $slide->posterUrl() }}" @endif
              >
                <source src="{{ $mediaUrl }}" type="{{ $slide->videoMimeType() }}">
              </video>
            @endif
            @if (!$isVideo || $slide->kicker || $buttonHref)
              <div class="banner_slide_content">
                <div class="container">
                  <div class="row">
                    <div class="{{ $index === 0 ? 'col-lg-7 col-9' : 'col-lg-6' }}">
                      <div class="banner_content overflow-hidden">
                        @if ($slide->kicker)
                          <h5 class="mb-3 staggered-animation font-weight-light" data-animation="slideInLeft" data-animation-delay="0.5s">{{ $slide->kicker }}</h5>
                        @endif
                        @if (!$isVideo)
                          <h2 class="staggered-animation" data-animation="slideInLeft" data-animation-delay="1s">{{ $slide->title }}</h2>
                        @endif
                        @if ($buttonHref)
                          <a class="btn btn-fill-out rounded-0 staggered-animation text-uppercase" href="{{ $buttonHref }}" data-animation="slideInLeft" data-animation-delay="1.5s">{{ $slide->button_label }}</a>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            @endif
          </div>
        @endforeach
      </div>
      @if ($homeSlides->count() > 1)
        <a class="carousel-control-prev" href="#carouselExampleControls" role="button" data-bs-slide="prev"><i class="ion-chevron-left"></i></a>
        <a class="carousel-control-next" href="#carouselExampleControls" role="button" data-bs-slide="next"><i class="ion-chevron-right"></i></a>
      @endif
    </div>
  </div>
  @endif

  <div class="main_content">
    <div class="section pb_20">
      <div class="container">
        <div class="row">
          <div class="col-md-6">
            <div class="single_banner">
              <img
                src="{{ $newArrivalBanner?->primaryImageUrl() ?? $sw('images/shop_banner_img1.jpg') }}"
                alt="{{ $newArrivalBanner?->name ?? 'Nouveautés Lialalionne' }}"
              >
              <div class="single_banner_info">
                <h5 class="single_bn_title1">Nouveautés</h5>
                <h3 class="single_bn_title">{{ $newArrivalBanner?->name ?? 'Nouvelle collection' }}</h3>
                <a href="{{ route('shop.catalog', ['selection' => 'nouveautes']) }}" class="single_bn_link">Voir</a>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="single_banner">
              <img
                src="{{ $specialOfferBanner?->primaryImageUrl() ?? $sw('images/shop_banner_img2.jpg') }}"
                alt="{{ $specialOfferBanner?->name ?? 'Offres spéciales Lialalionne' }}"
              >
              <div class="single_banner_info">
                <h3 class="single_bn_title">Offres spéciales</h3>
                <h4 class="single_bn_title1">
                  @if ($specialOfferDiscountPercent > 0)
                    Jusqu'à {{ $specialOfferDiscountPercent }}% de rabais
                  @else
                    {{ $specialOfferBanner?->name ?? 'Soins visage & corps' }}
                  @endif
                </h4>
                <a href="{{ route('shop.catalog', ['selection' => 'offres']) }}" class="single_bn_link">Acheter</a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section small_pt pb_70">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-6">
            <div class="heading_s1 text-center">
              <h2>Produits exclusifs</h2>
            </div>
          </div>
        </div>


        <div class="row">
          <div class="col-12">
            <div class="tab-style1">
              <ul class="nav nav-tabs justify-content-center" role="tablist">
                <li class="nav-item">
                  <a class="nav-link active" id="arrival-tab" data-bs-toggle="tab" href="#arrival" role="tab" aria-controls="arrival" aria-selected="true">Nouveautés</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="sellers-tab" data-bs-toggle="tab" href="#sellers" role="tab" aria-controls="sellers" aria-selected="false">Meilleures ventes</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="featured-tab" data-bs-toggle="tab" href="#featured" role="tab" aria-controls="featured" aria-selected="false">Vedettes</a>
                </li>
                <li class="nav-item">
                  <a class="nav-link" id="special-tab" data-bs-toggle="tab" href="#special" role="tab" aria-controls="special" aria-selected="false">Offres spéciales</a>
                </li>
              </ul>
            </div>

            <div class="tab-content">
              <div class="tab-pane fade show active" id="arrival" role="tabpanel" aria-labelledby="arrival-tab">
                <x-shopwise-exclusive-products-grid
                  :products="$newArrivalProducts"
                  :favorite-ids="$favoriteIds"
                  :cart-added-product-id="$cartAddedProductId"
                  :mark-first-as-new="true"
                  grid-key="arrival"
                />
              </div>
              <div class="tab-pane fade" id="sellers" role="tabpanel" aria-labelledby="sellers-tab">
                <x-shopwise-exclusive-products-grid
                  :products="$bestSellerProducts"
                  :favorite-ids="$favoriteIds"
                  :cart-added-product-id="$cartAddedProductId"
                  grid-key="sellers"
                />
              </div>
              <div class="tab-pane fade" id="featured" role="tabpanel" aria-labelledby="featured-tab">
                <x-shopwise-exclusive-products-grid
                  :products="$featuredTabProducts"
                  :favorite-ids="$favoriteIds"
                  :cart-added-product-id="$cartAddedProductId"
                  grid-key="featured-tab"
                />
              </div>
              <div class="tab-pane fade" id="special" role="tabpanel" aria-labelledby="special-tab">
                <x-shopwise-exclusive-products-grid
                  :products="$specialOfferProducts"
                  :favorite-ids="$favoriteIds"
                  :cart-added-product-id="$cartAddedProductId"
                  grid-key="special"
                />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section bg_light_blue2 pb-0 pt-md-0">
      <div class="container">
        <div class="row align-items-center flex-row-reverse">
          <div class="col-md-6 offset-md-1">
            <div class="medium_divider d-none d-md-block clearfix"></div>
            <div class="trand_banner_text text-center text-md-start">
              <div class="heading_s1 mb-3">
                <span class="sub_heading">Nouvelles tendances de saison !</span>
                <h2>{{ $seasonalHighlight?->name ?? 'Meilleure collection de saison' }}</h2>
              </div>
              <h5 class="mb-4">
                {{ $seasonalHighlight?->short_description ?? 'Profitez de nos offres sur les soins corporels' }}
              </h5>
              <a
                href="{{ $seasonalHighlight ? route('products.show', $seasonalHighlight) : route('shop.catalog', ['selection' => 'tendances']) }}"
                class="btn btn-fill-out rounded-0"
              >
                Magasinez maintenant
              </a>
            </div>
            <div class="medium_divider clearfix"></div>
          </div>
          <div class="col-md-5">
            <div class="text-center trading_img">
              <img
                src="{{ $seasonalHighlight?->primaryImageUrl() ?? $sw('images/tranding_img.png') }}"
                alt="{{ $seasonalHighlight?->name ?? 'Tendances de saison Lialalionne' }}"
              >
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-6">
            <div class="heading_s1 text-center">
              <h2>Produits en vedette</h2>
            </div>
          </div>
        </div>

        @if ($featuredProducts->isEmpty())
          <p class="text-center text-muted">Aucun produit vedette pour le moment.</p>
        @else
          <div class="row">
            <div class="col-md-12">
              <div
                wire:ignore
                class="product_slider carousel_slider owl-carousel owl-theme nav_style1"
                data-loop="true"
                data-dots="false"
                data-nav="true"
                data-margin="20"
                data-responsive='{"0":{"items": "1"}, "481":{"items": "2"}, "768":{"items": "3"}, "1199":{"items": "4"}}'
              >
                @foreach ($featuredProducts as $product)
                  <div class="item" wire:key="featured-slide-{{ $product->id }}">
                    <x-shopwise-product-item
                      :product="$product"
                      :favorite-ids="$favoriteIds"
                      :cart-added-product-id="$cartAddedProductId"
                    />
                  </div>
                @endforeach
              </div>
            </div>
          </div>
        @endif
      </div>
    </div>

    <div class="section bg_redon">
      <div class="container">
        <div class="row justify-content-center">
          <div class="col-md-6">
            <div class="heading_s1 text-center">
              <h2>Nos clients témoignent</h2>
            </div>
          </div>
        </div>
        <div class="row justify-content-center">
          <div class="col-lg-9">
            <div
              wire:ignore
              class="testimonial_wrap testimonial_style1 carousel_slider owl-carousel owl-theme nav_style2"
              data-nav="true"
              data-dots="false"
              data-center="true"
              data-loop="true"
              data-autoplay="true"
              data-items="1"
            >
              @foreach ($testimonials as $testimonial)
                <div class="testimonial_box">
                  <div class="testimonial_desc">
                    <p>{{ $testimonial['quote'] }}</p>
                  </div>
                  <div class="author_wrap">
                    <div class="author_img">
                      <img src="{{ $testimonial['image'] }}" alt="{{ $testimonial['name'] }}">
                    </div>
                    <div class="author_name">
                      <h6>{{ $testimonial['name'] }}</h6>
                      <span>{{ $testimonial['role'] }}</span>
                    </div>
                  </div>
                </div>
              @endforeach
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="section pb_70">
      <div class="container">
        <div class="row g-0">
          <div class="col-lg-4">
            <div class="icon_box icon_box_style1">
              <div class="icon"><i class="flaticon-shipped"></i></div>
              <div class="icon_box_content">
                <h5>Livraison</h5>
                <p>Expédition en RDC et retrait en boutique à Kinshasa.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="icon_box icon_box_style1">
              <div class="icon"><i class="flaticon-money-back"></i></div>
              <div class="icon_box_content">
                <h5>Paiement sécurisé</h5>
                <p>Mobile Money et carte bancaire.</p>
              </div>
            </div>
          </div>
          <div class="col-lg-4">
            <div class="icon_box icon_box_style1">
              <div class="icon"><i class="flaticon-support"></i></div>
              <div class="icon_box_content">
                <h5>Support client</h5>
                <p>Une équipe à votre écoute pour vos commandes.</p>
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

<livewire:shop.featured-welcome-popup />
</div>
