<x-lw-action
  action="toggle"
  :stop="true"
  class="rounded-full bg-white/90 p-2 shadow-sm ring-1 ring-stone-200 backdrop-blur transition hover:bg-white"
  aria-label="{{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
  title="{{ $isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris' }}"
>
  <svg class="h-5 w-5 {{ $isFavorite ? 'fill-brand-600 text-brand-600' : 'fill-none text-stone-500' }}" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" />
  </svg>
</x-lw-action>
