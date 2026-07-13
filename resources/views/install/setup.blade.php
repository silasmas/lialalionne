@extends('layouts.install')

@section('content')
<div class="min-h-screen bg-stone-950 px-4 py-10 text-stone-100">
  <div class="mx-auto max-w-4xl">
    <div class="mb-8 text-center">
      <img src="{{ asset('assets/logo.jpeg') }}" alt="Lialalionne" class="install-logo mx-auto mb-3">
      <h1 class="text-2xl font-semibold text-brand-300">Installation Lialalionne</h1>
      <p class="mt-2 text-sm text-stone-400">Configurez la base, l'administrateur et le mode Coming Soon.</p>
    </div>

    @if ($status['core_setup_complete'] ?? false)
      <div class="mb-6 rounded-lg border border-green-800 bg-green-950/40 p-5">
        <h2 class="mb-2 text-lg font-semibold text-green-300">Installation de base terminée</h2>
        <p class="mb-4 text-sm text-green-200/80">
          La base de données et l'administrateur sont prêts. Vous pouvez accéder à l'administration ou visiter le site.
          @if ($comingSoonEnabled)
            Le site affiche la page Coming Soon sur la page d'accueil.
          @endif
        </p>
        <div class="flex flex-wrap gap-3">
          <a
            href="{{ route('filament.admin.auth.login') }}"
            class="rounded bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-700"
          >
            Accéder à l'admin
          </a>
          <a
            href="{{ route('home') }}"
            class="rounded border border-brand-600 px-5 py-2.5 text-sm font-semibold text-brand-300 hover:bg-brand-950"
          >
            Voir le site
          </a>
          <a
            href="{{ route('filament.admin.pages.system-setup') }}"
            class="rounded border border-stone-600 px-5 py-2.5 text-sm text-stone-300 hover:bg-stone-800"
          >
            Paramètres système (admin)
          </a>
        </div>
      </div>
    @endif

    @if (session('install_flash_message'))
      <div class="install-alert install-alert--{{ session('install_flash_type') === 'success' ? 'success' : 'error' }}" role="alert">
        {{ session('install_flash_message') }}
      </div>
    @endif

    <div class="install-status-grid">
      @foreach ([
        ['label' => '.env', 'ok' => $status['env_file'] ?? false],
        ['label' => 'APP_KEY', 'ok' => $status['app_key'] ?? false],
        ['label' => 'BDD', 'ok' => $status['database_connection'] ?? false],
        ['label' => 'Migrations', 'ok' => empty($status['pending_migrations'] ?? []) && ($status['migrations_table'] ?? false)],
        ['label' => 'Storage', 'ok' => $status['storage_linked'] ?? false],
        ['label' => 'Admin', 'ok' => $status['admin_user'] ?? false],
      ] as $item)
        <div class="rounded border px-3 py-2 text-sm {{ $item['ok'] ? 'status-ok' : 'status-pending' }}">
          {{ $item['label'] }} : {{ $item['ok'] ? 'OK' : 'À faire' }}
        </div>
      @endforeach
    </div>

    @if ($dbError)
      <div class="install-alert install-alert--error mb-6" role="alert">
        Erreur connexion BDD : {{ $dbError }}
      </div>
    @endif

    <section class="mb-6 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">1. Paramètres de base (.env)</h2>
      <form method="POST" action="{{ route('install.environment') }}" class="grid gap-3 sm:grid-cols-2">
        @csrf
        @foreach ($editableKeys as $key => $meta)
          <div>
            <label class="mb-1 block text-xs uppercase tracking-wide text-stone-400" for="env-{{ $key }}">{{ $meta['label'] }}</label>
            @if (($meta['type'] ?? 'text') === 'boolean')
              <select id="env-{{ $key }}" name="{{ $key }}" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
                <option value="true" @selected(($envValues[$key] ?? '') === 'true')>true</option>
                <option value="false" @selected(($envValues[$key] ?? '') === 'false')>false</option>
              </select>
            @else
              <input
                id="env-{{ $key }}"
                type="{{ $meta['type'] === 'password' ? 'password' : 'text' }}"
                name="{{ $key }}"
                value="{{ $meta['type'] === 'password' ? '' : ($envValues[$key] ?? '') }}"
                placeholder="{{ $meta['placeholder'] ?? ($meta['type'] === 'password' ? 'Laisser vide pour conserver' : '') }}"
                class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm"
                autocomplete="off"
              >
            @endif
          </div>
        @endforeach
        <div class="sm:col-span-2 mt-2 flex flex-wrap gap-2">
          <button type="submit" class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">
            Enregistrer .env
          </button>
        </div>
      </form>
      <form method="POST" action="{{ route('install.app-key') }}" class="mt-2">
        @csrf
        <button type="submit" class="rounded border border-stone-600 px-4 py-2 text-sm hover:bg-stone-800">
          Générer APP_KEY
        </button>
      </form>
    </section>

    <section class="mb-6 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">2. Base de données</h2>
      <p class="mb-3 text-sm text-stone-400">
        Migrations en attente :
        @if (empty($status['pending_migrations'] ?? []))
          aucune
        @else
          {{ count($status['pending_migrations']) }}
        @endif
      </p>
      <div class="flex flex-wrap gap-2">
        <form method="POST" action="{{ route('install.migrate') }}">
          @csrf
          <button type="submit" class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">
            Exécuter les migrations
          </button>
        </form>
        <form method="POST" action="{{ route('install.storage-link') }}">
          @csrf
          <button type="submit" class="rounded border border-stone-600 px-4 py-2 text-sm hover:bg-stone-800">
            php artisan storage:link
          </button>
        </form>
      </div>
    </section>

    <section class="mb-6 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">3. Seeders</h2>
      <form method="POST" action="{{ route('install.seeders') }}" class="max-w-md">
        @csrf
        <select name="selectedSeeder" class="mb-3 w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @foreach ($seeders as $seeder)
            <option value="{{ $seeder }}" @selected($selectedSeeder === $seeder)>{{ class_basename(str_replace('\\', '/', $seeder)) }}</option>
          @endforeach
        </select>
        <button type="submit" class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">
          Exécuter le seeder
        </button>
      </form>
    </section>

    <section class="mb-6 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">4. Super administrateur</h2>
      <form method="POST" action="{{ route('install.admin') }}" class="grid gap-3 sm:grid-cols-2">
        @csrf
        <div>
          <label class="mb-1 block text-xs text-stone-400" for="adminName">Nom</label>
          <input id="adminName" type="text" name="adminName" value="{{ old('adminName', 'Admin Lialalionne') }}" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @error('adminName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400" for="adminEmail">E-mail</label>
          <input id="adminEmail" type="email" name="adminEmail" value="{{ old('adminEmail') }}" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @error('adminEmail') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400" for="adminPassword">Mot de passe</label>
          <input id="adminPassword" type="password" name="adminPassword" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @error('adminPassword') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400" for="adminPasswordConfirmation">Confirmation</label>
          <input id="adminPasswordConfirmation" type="password" name="adminPassword_confirmation" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2">
          <button type="submit" class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">
            Créer le super admin
          </button>
        </div>
      </form>
    </section>

    <section class="mb-8 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">5. Coming Soon</h2>
      <form method="POST" action="{{ route('install.coming-soon') }}">
        @csrf
        <label class="mb-4 flex items-center gap-2 text-sm">
          <input type="checkbox" name="comingSoonEnabled" value="1" @checked($comingSoonEnabled) class="rounded border-stone-600">
          Activer la page « bientôt disponible » (l'accueil / affiche Coming Soon)
        </label>
        <div class="grid gap-3 sm:grid-cols-2">
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs text-stone-400" for="comingSoonTitle">Titre</label>
            <input id="comingSoonTitle" type="text" name="comingSoonTitle" value="{{ $comingSoonTitle }}" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          </div>
          <div class="sm:col-span-2">
            <label class="mb-1 block text-xs text-stone-400" for="comingSoonMessage">Message</label>
            <textarea id="comingSoonMessage" name="comingSoonMessage" rows="3" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">{{ $comingSoonMessage }}</textarea>
          </div>
          <div>
            <label class="mb-1 block text-xs text-stone-400" for="comingSoonLaunchAt">Date de sortie</label>
            <input id="comingSoonLaunchAt" type="date" name="comingSoonLaunchAt" value="{{ $comingSoonLaunchAt }}" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          </div>
          <div>
            <label class="mb-1 block text-xs text-stone-400" for="comingSoonBypassSecret">Code accès manuel</label>
            <input id="comingSoonBypassSecret" type="text" name="comingSoonBypassSecret" value="{{ $comingSoonBypassSecret }}" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm" placeholder="ex: lialalionne2026">
          </div>
        </div>
        <button type="submit" class="mt-4 rounded border border-brand-600 px-4 py-2 text-sm text-brand-300 hover:bg-brand-950">
          Enregistrer Coming Soon
        </button>
      </form>
    </section>
  </div>
</div>
@endsection
