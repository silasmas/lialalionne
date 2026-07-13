<nav class="mb-8 flex flex-wrap gap-2 border-b border-stone-200 pb-4 text-sm">
  <a
    href="{{ route('account.dashboard') }}"
    wire:navigate
    class="rounded-lg px-3 py-2 font-medium {{ request()->routeIs('account.dashboard') ? 'bg-brand-50 text-brand-700' : 'text-stone-600 hover:bg-stone-100' }}"
  >
    Tableau de bord
  </a>
  <a
    href="{{ route('account.orders') }}"
    wire:navigate
    class="rounded-lg px-3 py-2 font-medium {{ request()->routeIs('account.orders*') ? 'bg-brand-50 text-brand-700' : 'text-stone-600 hover:bg-stone-100' }}"
  >
    Mes commandes
  </a>
  <a
    href="{{ route('account.favorites') }}"
    wire:navigate
    class="rounded-lg px-3 py-2 font-medium {{ request()->routeIs('account.favorites') ? 'bg-brand-50 text-brand-700' : 'text-stone-600 hover:bg-stone-100' }}"
  >
    Favoris
  </a>
  <form method="POST" action="{{ route('account.logout') }}" class="ml-auto">
    @csrf
    <button type="submit" class="rounded-lg px-3 py-2 font-medium text-stone-600 hover:bg-stone-100">
      Déconnexion
    </button>
  </form>
</nav>
