@props([
  'product',
  'favoriteIds' => [],
  'cartAddedProductId' => null,
  'displayImageUrl' => null,
  'showNewBadge' => false,
])

@php
  use App\Support\ShopwiseAssets;

  $isFavorite = in_array($product->id, $favoriteIds, true);
  $justAdded = $cartAddedProductId === $product->id;
  $productUrl = route('products.show', $product);
  $imageUrl = $displayImageUrl
    ?? $product->primaryImageUrl()
    ?? ShopwiseAssets::productImageUrl($product->id);
  $discountPercent = null;
  $cartAction = 'addProductToCart(' . $product->id . ')';
  $compareAction = 'addProductToCompare(' . $product->id . ')';
  $favoriteAction = 'toggleProductFavorite(' . $product->id . ')';
  $quickViewAction = 'openQuickView(' . $product->id . ')';

  if ($product->hasDiscount() && (float) $product->compare_at_price > 0) {
    $discountPercent = (int) round((1 - ((float) $product->price / (float) $product->compare_at_price)) * 100);
  }

  $ratingWidth = 60 + (($product->id * 17) % 35);
  $reviewCount = 5 + (($product->id * 3) % 40);
@endphp

<div class="product">
  @if ($showNewBadge)
    <span class="pr_flash">Nouveau</span>
  @endif

  <div class="product_img">
    @if ($product->hasDiscount() && $discountPercent && !$showNewBadge)
      <x-discount-ribbon :percent="$discountPercent" />
    @endif
    <a href="{{ $productUrl }}">
      <img src="{{ $imageUrl }}" alt="{{ $product->name }}">
    </a>
    <div class="product_action_box">
      <ul class="list_none pr_action_btn">
        <li class="add-to-cart">
          <x-lw-action :action="$cartAction" tag="a" title="Ajouter au panier">
            <i class="icon-basket-loaded"></i> {{ $justAdded ? 'Ajouté' : 'Panier' }}
          </x-lw-action>
        </li>
        <li>
          <x-lw-action :action="$compareAction" tag="a" title="Comparer">
            <i class="icon-shuffle"></i>
          </x-lw-action>
        </li>
        <li>
          <x-lw-action :action="$quickViewAction" tag="a" title="Aperçu rapide">
            <i class="icon-magnifier-add"></i>
          </x-lw-action>
        </li>
        <li>
          <x-lw-action
            :action="$favoriteAction"
            tag="a"
            title="{{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
          >
            <i class="icon-heart"></i>
          </x-lw-action>
        </li>
      </ul>
    </div>
  </div>

  <div class="product_info">
    <h6 class="product_title">
      <a href="{{ $productUrl }}">{{ $product->name }}</a>
    </h6>
    <div class="product_price">
      <span class="price">{{ $product->formatPrice() }}</span>
      @if ($product->hasDiscount())
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
        <p>{{ \Illuminate\Support\Str::limit($product->short_description, 120) }}</p>
      </div>
    @endif
    <div class="list_product_action_box">
      <ul class="list_none pr_action_btn">
        <li class="add-to-cart">
          <x-lw-action :action="$cartAction" tag="a">
            <i class="icon-basket-loaded"></i> {{ $justAdded ? 'Ajouté' : 'Panier' }}
          </x-lw-action>
        </li>
        <li>
          <x-lw-action :action="$compareAction" tag="a" title="Comparer">
            <i class="icon-shuffle"></i>
          </x-lw-action>
        </li>
        <li>
          <x-lw-action :action="$quickViewAction" tag="a" title="Aperçu rapide">
            <i class="icon-magnifier-add"></i>
          </x-lw-action>
        </li>
        <li>
          <x-lw-action :action="$favoriteAction" tag="a" title="Favoris">
            <i class="icon-heart"></i>
          </x-lw-action>
        </li>
      </ul>
    </div>
  </div>
</div>
