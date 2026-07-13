<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Lialalionne — Soins corporels' }}</title>
  @isset($metaDescription)
    <meta name="description" content="{{ $metaDescription }}">
  @endisset
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<body class="min-h-screen bg-stone-50 text-stone-900 antialiased">
  <header class="border-b border-stone-200 bg-white">
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
      <a href="{{ route('home') }}" wire:navigate class="text-xl font-semibold tracking-tight text-brand-700">
        Lialalionne
      </a>
      <nav class="flex items-center gap-6 text-sm font-medium text-stone-600">
        <a href="{{ route('home') }}" wire:navigate class="hover:text-brand-700 {{ request()->routeIs('home') ? 'text-brand-700' : '' }}">
          Accueil
        </a>
        <a href="{{ route('shop.catalog') }}" wire:navigate class="hover:text-brand-700 {{ request()->routeIs(['shop.catalog', 'products.show']) ? 'text-brand-700' : '' }}">
          Boutique
        </a>
        <livewire:shop.cart-icon />
        <livewire:shop.currency-selector />
        @auth
          <a href="{{ route('account.dashboard') }}" wire:navigate class="hover:text-brand-700 {{ request()->routeIs('account.*') ? 'text-brand-700' : '' }}">
            Mon compte
          </a>
        @else
          <a href="{{ route('account.login') }}" wire:navigate class="hover:text-brand-700 {{ request()->routeIs(['account.login', 'account.register']) ? 'text-brand-700' : '' }}">
            Connexion
          </a>
        @endauth
        <a href="/admin" class="hover:text-brand-700">Admin</a>
      </nav>
    </div>
  </header>

  <main>
    {{ $slot }}
  </main>

  <footer class="mt-16 border-t border-stone-200 bg-white">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
      <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
        <div>
          <p class="text-sm font-semibold text-stone-900">Lialalionne</p>
          <p class="mt-1 text-sm text-stone-500">Soins corporels premium.</p>
        </div>
        <nav class="flex flex-wrap gap-x-6 gap-y-2 text-sm text-stone-600">
          <a href="{{ route('legal.show', 'cgv') }}" wire:navigate class="hover:text-brand-700">CGV</a>
          <a href="{{ route('legal.show', 'confidentialite') }}" wire:navigate class="hover:text-brand-700">Confidentialité</a>
          <a href="{{ route('legal.show', 'retours') }}" wire:navigate class="hover:text-brand-700">Retours</a>
          <a href="#" data-open-cookie-consent class="hover:text-brand-700">Gérer les cookies</a>
          <a href="{{ route('shop.catalog') }}" wire:navigate class="hover:text-brand-700">Boutique</a>
        </nav>
      </div>
      <p class="mt-8 text-center text-sm text-stone-500 sm:text-left">
        &copy; {{ date('Y') }} Lialalionne. Tous droits réservés.
      </p>
    </div>
  </footer>

  <x-cookie-consent />

  @livewireScripts
</body>
</html>
