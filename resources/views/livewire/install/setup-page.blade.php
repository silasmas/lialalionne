<div class="min-h-screen bg-stone-950 px-4 py-10 text-stone-100">
  <div class="mx-auto max-w-4xl">
    <div class="mb-8 text-center">
      <img src="{{ asset('assets/logo.jpeg') }}" alt="Lialalionne" class="mx-auto mb-4 max-h-20">
      <h1 class="text-2xl font-semibold text-brand-300">Installation Lialalionne</h1>
      <p class="mt-2 text-sm text-stone-400">Configurez la base, l'administrateur et le mode Coming Soon.</p>
    </div>

    @if ($flashMessage)
      <div class="mb-6 rounded border px-4 py-3 text-sm {{ $flashType === 'success' ? 'border-green-700 bg-green-950 text-green-200' : 'border-red-700 bg-red-950 text-red-200' }}">
        {{ $flashMessage }}
      </div>
    @endif

    <div class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
      @foreach ([
        ['label' => '.env', 'ok' => $status['env_file'] ?? false],
        ['label' => 'APP_KEY', 'ok' => $status['app_key'] ?? false],
        ['label' => 'BDD', 'ok' => $status['database_connection'] ?? false],
        ['label' => 'Migrations', 'ok' => empty($status['pending_migrations'] ?? []) && ($status['migrations_table'] ?? false)],
        ['label' => 'Storage', 'ok' => $status['storage_linked'] ?? false],
        ['label' => 'Admin', 'ok' => $status['admin_user'] ?? false],
      ] as $item)
        <div class="rounded border px-3 py-2 text-sm {{ $item['ok'] ? 'border-green-700 text-green-300' : 'border-stone-700 text-stone-400' }}">
          {{ $item['label'] }} : {{ $item['ok'] ? 'OK' : 'À faire' }}
        </div>
      @endforeach
    </div>

    <section class="mb-6 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">1. Paramètres de base (.env)</h2>
      <div class="grid gap-3 sm:grid-cols-2">
        @foreach ($editableKeys as $key => $meta)
          <div>
            <label class="mb-1 block text-xs uppercase tracking-wide text-stone-400">{{ $meta['label'] }}</label>
            @if (($meta['type'] ?? 'text') === 'boolean')
              <select wire:model="envValues.{{ $key }}" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
                <option value="true">true</option>
                <option value="false">false</option>
              </select>
            @else
              <input
                type="{{ $meta['type'] === 'password' ? 'password' : 'text' }}"
                wire:model="envValues.{{ $key }}"
                placeholder="{{ $meta['placeholder'] ?? '' }}"
                class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm"
              >
            @endif
          </div>
        @endforeach
      </div>
      <div class="mt-4 flex flex-wrap gap-2">
        <button type="button" wire:click="saveEnvironment" class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">Enregistrer .env</button>
        <button type="button" wire:click="generateAppKey" class="rounded border border-stone-600 px-4 py-2 text-sm hover:bg-stone-800">Générer APP_KEY</button>
      </div>
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
        <button type="button" wire:click="runMigrations" class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">Exécuter les migrations</button>
        <button type="button" wire:click="linkStorage" class="rounded border border-stone-600 px-4 py-2 text-sm hover:bg-stone-800">php artisan storage:link</button>
      </div>
    </section>

    <section class="mb-6 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">3. Seeders</h2>
      <div class="mb-3 max-w-md">
        <select wire:model="selectedSeeder" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @foreach ($seeders as $seeder)
            <option value="{{ $seeder }}">{{ class_basename(str_replace('\\', '/', $seeder)) }}</option>
          @endforeach
        </select>
      </div>
      <button type="button" wire:click="runSeeders" class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">Exécuter le seeder</button>
    </section>

    <section class="mb-6 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">4. Super administrateur</h2>
      <div class="grid gap-3 sm:grid-cols-2">
        <div>
          <label class="mb-1 block text-xs text-stone-400">Nom</label>
          <input type="text" wire:model="adminName" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @error('adminName') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400">E-mail</label>
          <input type="email" wire:model="adminEmail" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @error('adminEmail') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400">Mot de passe</label>
          <input type="password" wire:model="adminPassword" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
          @error('adminPassword') <p class="mt-1 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400">Confirmation</label>
          <input type="password" wire:model="adminPasswordConfirmation" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
        </div>
      </div>
      <button type="button" wire:click="createSuperAdmin" class="mt-4 rounded bg-brand-600 px-4 py-2 text-sm font-semibold hover:bg-brand-700">Créer le super admin</button>
    </section>

    <section class="mb-8 rounded-lg border border-stone-800 bg-stone-900 p-5">
      <h2 class="mb-4 text-lg font-semibold text-brand-300">5. Coming Soon</h2>
      <label class="mb-4 flex items-center gap-2 text-sm">
        <input type="checkbox" wire:model="comingSoonEnabled" class="rounded border-stone-600">
        Activer la page « bientôt disponible » (bloque la boutique publique)
      </label>
      <div class="grid gap-3 sm:grid-cols-2">
        <div class="sm:col-span-2">
          <label class="mb-1 block text-xs text-stone-400">Titre</label>
          <input type="text" wire:model="comingSoonTitle" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
        </div>
        <div class="sm:col-span-2">
          <label class="mb-1 block text-xs text-stone-400">Message</label>
          <textarea wire:model="comingSoonMessage" rows="3" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm"></textarea>
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400">Date de sortie</label>
          <input type="date" wire:model="comingSoonLaunchAt" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm">
        </div>
        <div>
          <label class="mb-1 block text-xs text-stone-400">Code accès manuel</label>
          <input type="text" wire:model="comingSoonBypassSecret" class="w-full rounded border border-stone-700 bg-stone-950 px-3 py-2 text-sm" placeholder="ex: lialalionne2026">
        </div>
      </div>
      <button type="button" wire:click="saveComingSoon" class="mt-4 rounded border border-brand-600 px-4 py-2 text-sm text-brand-300 hover:bg-brand-950">Enregistrer Coming Soon</button>
    </section>

    <div class="flex justify-end">
      <button type="button" wire:click="finish" class="rounded bg-green-700 px-6 py-3 text-sm font-semibold hover:bg-green-600">
        Terminer l'installation → Admin
      </button>
    </div>
  </div>
</div>
