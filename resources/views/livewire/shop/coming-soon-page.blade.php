<div class="flex min-h-screen flex-col items-center justify-center px-4 py-12">
  <div class="w-full max-w-xl text-center">
    <img
      src="{{ asset('assets/logo.jpeg') }}"
      alt="Lialalionne"
      class="mx-auto mb-8 max-h-14 w-auto"
    >

    <p class="mb-2 text-sm uppercase tracking-[0.35em] text-brand-400">Luxury</p>
    <h1 class="mb-4 text-3xl font-semibold text-brand-200 sm:text-4xl">{{ $title }}</h1>

    @if ($message)
      <p class="mx-auto mb-6 max-w-lg text-base leading-relaxed text-stone-300">{{ $message }}</p>
    @endif

    @if ($launchAt)
      <p class="mb-8 text-sm text-brand-300">
        Sortie prévue : <strong>{{ \Illuminate\Support\Carbon::parse($launchAt)->translatedFormat('d F Y') }}</strong>
      </p>
    @endif

    <div class="mx-auto mb-8 h-px w-24 bg-brand-600"></div>

    <p class="text-sm text-stone-400">
      Site en construction — merci de votre patience.
    </p>

    @if ($hasBypass)
      <form wire:submit.prevent="unlock" class="mx-auto mt-10 max-w-sm text-left">
        <label for="bypassCode" class="mb-2 block text-xs uppercase tracking-wide text-stone-400">
          Accès équipe (code secret)
        </label>
        <div class="flex gap-2">
          <input
            id="bypassCode"
            type="password"
            wire:model="bypassCode"
            class="flex-1 rounded border border-stone-700 bg-stone-900 px-3 py-2 text-sm text-white focus:border-brand-500 focus:outline-none"
            placeholder="Code d'accès"
          >
          <button
            type="submit"
            class="rounded bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-700"
          >
            Entrer
          </button>
        </div>
        @if ($unlockMessage)
          <p class="mt-2 text-sm text-red-400">{{ $unlockMessage }}</p>
        @endif
      </form>
    @endif
  </div>
</div>
