@props([
  'product',
  'favoriteIds' => [],
  'cartAddedProductId' => null,
  'interactive' => true,
])

@php
  $isFavorite = in_array($product->id, $favoriteIds, true);
  $justAdded = $cartAddedProductId === $product->id;
  $productUrl = route('products.show', $product);
@endphp

<div class="group relative flex flex-col overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm transition hover:border-brand-200 hover:shadow-md">
  <div class="relative aspect-square overflow-hidden bg-stone-100">
    @if ($interactive)
      <div class="pointer-events-none absolute inset-x-0 top-0 z-20 h-24 bg-gradient-to-b from-white/95 via-white/60 to-transparent"></div>

      <div class="absolute inset-x-0 top-0 z-40 flex items-start justify-between p-3">
        <div class="pointer-events-auto flex flex-col items-start gap-1">
          <x-lw-action
            :action="'addProductToCart(' . $product->id . ')'"
            :stop="true"
            class="rounded-full bg-white p-2.5 text-stone-800 shadow-lg ring-2 ring-white/90 transition hover:bg-brand-600 hover:text-white"
            aria-label="Ajouter au panier"
            title="Ajouter au panier"
          >
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
            </svg>
          </x-lw-action>
          @if ($justAdded)
            <span class="rounded bg-green-600 px-2 py-0.5 text-xs font-medium text-white shadow">Ajouté</span>
          @endif
        </div>

        <x-lw-action
          :action="'toggleProductFavorite(' . $product->id . ')'"
          :stop="true"
          class="pointer-events-auto rounded-full bg-white p-2.5 text-stone-800 shadow-lg ring-2 ring-white/90 transition hover:bg-brand-50 hover:text-brand-700"
          aria-label="{{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
          title="{{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
        >
          <svg class="h-5 w-5 {{ $isFavorite ? 'fill-brand-600 text-brand-600' : 'fill-none text-stone-700' }}" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
          </svg>
        </x-lw-action>
      </div>
    @endif

    @if ($product->primaryImageUrl())
      <img
        src="{{ $product->primaryImageUrl() }}"
        alt="{{ $product->name }}"
        class="h-full w-full object-cover transition duration-300 group-hover:scale-105"
        loading="lazy"
      />
    @else
      <div class="flex h-full items-center justify-center bg-stone-200 text-stone-400">
        <svg class="h-16 w-16" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
        </svg>
      </div>
    @endif

    @if ($product->hasDiscount())
      <span class="pointer-events-none absolute bottom-3 left-3 z-20 rounded-full bg-brand-600 px-2 py-0.5 text-xs font-semibold text-white">
        Promo
      </span>
    @endif

    @if (!$product->isInStock())
      <span class="pointer-events-none absolute bottom-3 right-3 z-20 rounded-full bg-stone-800 px-2 py-0.5 text-xs font-semibold text-white">
        Rupture
      </span>
    @endif
  </div>

  <a href="{{ $productUrl }}" wire:navigate class="flex flex-1 flex-col p-4">
    <p class="text-xs uppercase tracking-wide text-brand-600">
      {{ $product->category->name }}
    </p>
    <h3 class="mt-1 line-clamp-2 font-medium text-stone-900 group-hover:text-brand-700">
      {{ $product->name }}
    </h3>

    @if ($product->short_description)
      <p class="mt-1 line-clamp-2 text-sm text-stone-500">
        {{ $product->short_description }}
      </p>
    @endif

    <div class="mt-auto flex items-baseline gap-2 pt-3">
      <span class="text-lg font-semibold text-stone-900">
        {{ $product->formatPrice() }}
      </span>
      @if ($product->hasDiscount())
        <span class="text-sm text-stone-400 line-through">
          {{ $product->formatPrice($product->compare_at_price) }}
        </span>
      @endif
    </div>
  </a>
</div>
