<div class="dashboard_menu">
  <ul class="nav nav-tabs flex-column" role="tablist">
    <li class="nav-item">
      <a
        class="nav-link {{ request()->routeIs('account.dashboard') ? 'active' : '' }}"
        href="{{ route('account.dashboard') }}"
      >
        <i class="ti-layout-grid2"></i> Tableau de bord
      </a>
    </li>
    <li class="nav-item">
      <a
        class="nav-link {{ request()->routeIs('account.orders*') ? 'active' : '' }}"
        href="{{ route('account.orders') }}"
      >
        <i class="ti-shopping-cart-full"></i> Commandes
      </a>
    </li>
    <li class="nav-item">
      <a
        class="nav-link {{ request()->routeIs('account.favorites') ? 'active' : '' }}"
        href="{{ route('account.favorites') }}"
      >
        <i class="ti-heart"></i> Favoris
      </a>
    </li>
    <li class="nav-item">
      <form method="POST" action="{{ route('account.logout') }}">
        @csrf
        <button type="submit" class="nav-link border-0 bg-transparent text-start w-100">
          <i class="ti-lock"></i> Déconnexion
        </button>
      </form>
    </li>
  </ul>
</div>
