<a
  href="{{ route('shop.cart') }}"
  wire:navigate
  class="relative flex items-center gap-1 text-stone-600 hover:text-brand-700 {{ request()->routeIs('shop.cart') ? 'text-brand-700' : '' }}"
  aria-label="Panier ({{ $count }} article{{ $count > 1 ? 's' : '' }})"
>
  <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
  </svg>
  <span class="hidden sm:inline">Panier</span>
  @if ($count > 0)
    <span class="absolute -right-2 -top-2 flex h-5 min-w-[1.25rem] items-center justify-center rounded-full bg-brand-600 px-1 text-xs font-bold text-white">
      {{ $count > 99 ? '99+' : $count }}
    </span>
  @endif
</a>
