@php
  $megaMenuBanners = [
    [
      'image' => $sw('images/menu_banner1.jpg'),
      'discount' => '10% de rabais',
      'title' => 'Nouveautés',
      'url' => route('shop.catalog', ['tri' => 'newest']),
    ],
    [
      'image' => $sw('images/menu_banner2.jpg'),
      'discount' => '15% de rabais',
      'title' => 'Soins visage',
      'url' => route('shop.catalog'),
    ],
    [
      'image' => $sw('images/menu_banner3.jpg'),
      'discount' => '23% de rabais',
      'title' => 'Soins corps',
      'url' => route('shop.catalog'),
    ],
  ];

  $megaMenuColumns = $shopwiseCategories->take(4);

  if ($megaMenuColumns->isEmpty()) {
    $megaMenuColumns = collect([
      (object) [
        'id' => null,
        'name' => 'Boutique',
        'products' => collect(),
      ],
    ]);
  }
@endphp

<header class="header_wrap fixed-top header_with_topbar">
  <div class="top-header d-none d-md-block">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6">
          <div class="d-flex align-items-center">
            <livewire:shop.currency-selector theme="shopwise" />
            <ul class="contact_detail text-center text-lg-start ms-3 mb-0">
              <li><i class="ti-mobile"></i><span>Kinshasa, RDC</span></li>
            </ul>
          </div>
        </div>
        <div class="col-md-6">
          <div class="text-center text-md-end">
            <ul class="header_list">
              <li><a href="{{ route('shop.compare') }}"><i class="ti-control-shuffle"></i><span>Comparer</span></a></li>
              @auth
                <li><a href="{{ route('account.dashboard') }}"><i class="ti-user"></i><span>Mon compte</span></a></li>
              @else
                <li><a href="{{ route('account.login') }}"><i class="ti-user"></i><span>Connexion</span></a></li>
              @endauth
            </ul>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="bottom_header dark_skin main_menu_uppercase">
    <div class="container">
      <nav class="navbar navbar-expand-lg">
        <a class="navbar-brand" href="{{ route('home') }}">
          <img class="logo_light" src="{{ asset('assets/logo.jpeg') }}" alt="Lialalionne">
          <img class="logo_dark" src="{{ asset('assets/logo.jpeg') }}" alt="Lialalionne">
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-expanded="false">
          <span class="ion-android-menu"></span>
        </button>
        <div class="collapse navbar-collapse justify-content-end" id="navbarSupportedContent">
          <ul class="navbar-nav">
            <li>
              <a class="nav-link nav_item {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Accueil</a>
            </li>
            <li>
              <a class="nav-link nav_item {{ request()->routeIs('shop.about') ? 'active' : '' }}" href="{{ route('shop.about') }}">À propos</a>
            </li>
            <li class="dropdown dropdown-mega-menu">
              <a class="dropdown-toggle nav-link {{ request()->routeIs(['shop.catalog', 'products.show']) ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">Produits</a>
              <div class="dropdown-menu">
                <ul class="mega-menu d-lg-flex">
                  @foreach ($megaMenuColumns as $category)
                    <li class="mega-menu-col col-lg-3">
                      <ul>
                        <li class="dropdown-header">{{ $category->name }}</li>
                        <li>
                          <a class="dropdown-item nav-link nav_item" href="{{ $category->id ? route('shop.catalog', ['categorie' => $category->id]) : route('shop.catalog') }}">
                            Voir tout
                          </a>
                        </li>
                        @forelse ($category->products ?? [] as $product)
                          <li>
                            <a class="dropdown-item nav-link nav_item" href="{{ route('products.show', $product) }}">
                              {{ $product->name }}
                            </a>
                          </li>
                        @empty
                          <li>
                            <a class="dropdown-item nav-link nav_item" href="{{ route('shop.catalog') }}">
                              Découvrir la boutique
                            </a>
                          </li>
                        @endforelse
                      </ul>
                    </li>
                  @endforeach
                </ul>
                <div class="d-lg-flex menu_banners row g-3 px-3">
                  @foreach ($megaMenuBanners as $banner)
                    <div class="col-sm-4">
                      <div class="header-banner">
                        <img src="{{ $banner['image'] }}" alt="{{ $banner['title'] }}">
                        <div class="banne_info">
                          <h6>{{ $banner['discount'] }}</h6>
                          <h4>{{ $banner['title'] }}</h4>
                          <a href="{{ $banner['url'] }}">Acheter</a>
                        </div>
                      </div>
                    </div>
                  @endforeach
                </div>
              </div>
            </li>
            <li>
              <a class="nav-link nav_item {{ request()->routeIs('shop.catalog') ? 'active' : '' }}" href="{{ route('shop.catalog') }}">Boutique</a>
            </li>
            @auth
              <li class="dropdown">
                <a
                  class="dropdown-toggle nav-link {{ request()->routeIs('account.*') ? 'active' : '' }}"
                  href="#"
                  data-bs-toggle="dropdown"
                >
                  Mon compte
                </a>
                <div class="dropdown-menu">
                  <ul>
                    <li>
                      <a class="dropdown-item nav-link nav_item" href="{{ route('account.dashboard') }}">Espace client</a>
                    </li>
                    <li>
                      <a class="dropdown-item nav-link nav_item" href="{{ route('account.orders') }}">Mes commandes</a>
                    </li>
                    <li>
                      <a class="dropdown-item nav-link nav_item" href="{{ route('account.favorites') }}">Mes favoris</a>
                    </li>
                    <li>
                      <form method="POST" action="{{ route('account.logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item nav-link nav_item border-0 bg-transparent w-100 text-start">
                          Déconnexion
                        </button>
                      </form>
                    </li>
                  </ul>
                </div>
              </li>
            @else
              <li>
                <a class="nav-link nav_item {{ request()->routeIs('account.login') ? 'active' : '' }}" href="{{ route('account.login') }}">Connexion</a>
              </li>
            @endauth
          </ul>
        </div>
        <ul class="navbar-nav attr-nav align-items-center">
          <li class="dropdown">
            @auth
              <a
                class="nav-link"
                href="#"
                data-bs-toggle="dropdown"
                aria-expanded="false"
                aria-label="Mon espace client"
                title="Mon espace client"
              >
                <i class="ti-user"></i>
              </a>
              <div class="dropdown-menu dropdown-menu-end account_dropdown_box">
                <ul class="list-unstyled mb-0 py-2">
                  <li>
                    <a class="dropdown-item" href="{{ route('account.dashboard') }}">Espace client</a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('account.orders') }}">Mes commandes</a>
                  </li>
                  <li>
                    <a class="dropdown-item" href="{{ route('account.favorites') }}">Mes favoris</a>
                  </li>
                  <li>
                    <form method="POST" action="{{ route('account.logout') }}">
                      @csrf
                      <button type="submit" class="dropdown-item border-0 bg-transparent w-100 text-start">
                        Déconnexion
                      </button>
                    </form>
                  </li>
                </ul>
              </div>
            @else
              <a
                class="nav-link"
                href="{{ route('account.login') }}"
                aria-label="Connexion"
                title="Connexion"
              >
                <i class="ti-user"></i>
              </a>
            @endauth
          </li>
          <livewire:shop.cart-icon theme="shopwise" />
        </ul>
      </nav>
    </div>
  </div>
</header>
