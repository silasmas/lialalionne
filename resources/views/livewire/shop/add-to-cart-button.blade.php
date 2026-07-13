<div class="inline-flex flex-col items-end">
  <x-lw-action
    action="addToCart"
    :stop="true"
    class="rounded-full bg-white/90 p-2 shadow-sm ring-1 ring-stone-200 backdrop-blur transition hover:bg-brand-50 hover:text-brand-700"
    aria-label="Ajouter au panier"
    title="Ajouter au panier"
  >
    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
    </svg>
  </x-lw-action>
  @if ($message)
    <span class="mt-1 text-xs font-medium text-green-600">{{ $message }}</span>
  @endif
  @error('cart')
    <span class="mt-1 max-w-[8rem] text-right text-xs text-red-600">{{ $message }}</span>
  @enderror
</div>
