<div>
  <x-shopwise-breadcrumb
    title="Boutique"
    :items="[['label' => 'Boutique', 'url' => route('shop.catalog')]]"
  />

  <div class="main_content">
    <div class="section">
      <div class="container">
        <div class="row">
          <div class="col-lg-9">
            <div class="row align-items-center mb-4 pb-1">
              <div class="col-12">
                <div class="product_header">
                  <div class="product_header_left">
                    <div class="custom_select">
                      <select wire:model.live="sort" class="form-control form-control-sm">
                        <option value="featured">Tri par défaut</option>
                        <option value="newest">Nouveautés</option>
                        <option value="name">Nom A–Z</option>
                        <option value="price_asc">Prix croissant</option>
                        <option value="price_desc">Prix décroissant</option>
                      </select>
                    </div>
                  </div>
                  <div class="product_header_right">
                    <div class="products_view">
                      <a
                        href="#"
                        wire:click.prevent="setGridView"
                        class="shorting_icon grid {{ $viewMode === 'grid' ? 'active' : '' }}"
                        title="Vue grille"
                      >
                        <i class="ti-view-grid"></i>
                      </a>
                      <a
                        href="#"
                        wire:click.prevent="setListView"
                        class="shorting_icon list {{ $viewMode === 'list' ? 'active' : '' }}"
                        title="Vue liste"
                      >
                        <i class="ti-layout-list-thumb"></i>
                      </a>
                    </div>
                    <p class="mb-0 ms-3 d-none d-md-inline">
                      {{ $products->total() }} produit{{ $products->total() > 1 ? 's' : '' }}
                    </p>
                  </div>
                </div>
              </div>
            </div>


            @if ($products->isEmpty())
              <div class="text-center py-5">
                <p class="text-muted">Aucun produit ne correspond à votre recherche.</p>
                <button type="button" wire:click="resetFilters" class="btn btn-fill-out btn-sm">
                  Voir tous les produits
                </button>
              </div>
            @else
              <div class="row shop_container {{ $viewMode === 'list' ? 'list' : 'grid' }}">
                @foreach ($products as $product)
                  <div class="col-md-4 col-6">
                    <x-shopwise-product-item
                      :product="$product"
                      :favorite-ids="$favoriteIds"
                      :cart-added-product-id="$cartAddedProductId"
                    />
                  </div>
                @endforeach
              </div>

              <div class="row">
                <div class="col-12">
                  <div class="mt-3 pagination_style1">
                    {{ $products->links('pagination::bootstrap-5') }}
                  </div>
                </div>
              </div>
            @endif
          </div>

          <div class="col-lg-3 order-lg-first mt-4 pt-2 mt-lg-0 pt-lg-0">
            <div class="sidebar">
              <div class="widget">
                <h5 class="widget_title">Rechercher</h5>
                <input
                  type="search"
                  wire:model.live.debounce.300ms="search"
                  class="form-control form-control-sm"
                  placeholder="Nom, description..."
                >
              </div>

              <div class="widget">
                <h5 class="widget_title">Catégories</h5>
                <ul class="widget_categories">
                  <li>
                    <a
                      href="#"
                      wire:click.prevent="$set('categoryId', null)"
                      class="{{ $categoryId === null ? 'active' : '' }}"
                    >
                      <span class="categories_name">Toutes</span>
                    </a>
                  </li>
                  @foreach ($categories as $category)
                    <li>
                      <a
                        href="#"
                        wire:click.prevent="$set('categoryId', {{ $category->id }})"
                        class="{{ (int) $categoryId === (int) $category->id ? 'active' : '' }}"
                      >
                        <span class="categories_name">{{ $category->name }}</span>
                        <span class="categories_num">({{ $category->products_count }})</span>
                      </a>
                    </li>
                  @endforeach
                </ul>
              </div>

              @if ($search || $categoryId || $sort !== 'featured')
                <div class="widget">
                  <button type="button" wire:click="resetFilters" class="btn btn-outline-dark btn-sm w-100">
                    Réinitialiser les filtres
                  </button>
                </div>
              @endif

              <div class="widget">
                <div class="shop_banner">
                  <div class="banner_img overlay_bg_20">
                    <img src="{{ $sw('images/sidebar_banner_img.jpg') }}" alt="Promotion Lialalionne">
                  </div>
                  <div class="shop_bn_content2 text_white">
                    <h5 class="text-uppercase shop_subtitle">Nouvelle collection</h5>
                    <h3 class="text-uppercase shop_title">Jusqu'à 30% de rabais</h3>
                    <a href="{{ route('shop.catalog') }}" class="btn btn-white rounded-0 btn-sm text-uppercase">Acheter</a>
                  </div>
                </div>
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
