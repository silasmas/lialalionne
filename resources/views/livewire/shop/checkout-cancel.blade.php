<div class="mx-auto max-w-xl px-4 py-16 text-center sm:px-6">
  <h1 class="text-2xl font-bold text-stone-900">Paiement annulé</h1>
  <p class="mt-4 text-stone-600">
    Votre paiement n'a pas été finalisé.
    @if ($order)
      La commande <strong>{{ $order->order_number }}</strong> reste en attente de paiement.
    @endif
  </p>
  <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:justify-center">
    <a href="{{ route('shop.checkout') }}" wire:navigate class="rounded-lg bg-brand-600 px-6 py-3 text-sm font-semibold text-white hover:bg-brand-700">
      Réessayer le paiement
    </a>
    <a href="{{ route('shop.cart') }}" wire:navigate class="rounded-lg border border-stone-300 px-6 py-3 text-sm font-medium text-stone-700 hover:border-brand-300">
      Retour au panier
    </a>
  </div>
</div>
