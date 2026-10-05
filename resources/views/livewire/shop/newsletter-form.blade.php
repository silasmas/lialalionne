<div class="newsletter_form">
  @if ($subscribed)
    <p class="mb-0">Merci ! Vous êtes bien inscrit(e) à notre newsletter.</p>
  @else
    <form wire:submit="subscribe" novalidate>
      <input
        type="email"
        wire:model="email"
        required
        class="form-control rounded-0 @error('email') is-invalid @enderror"
        placeholder="Votre adresse e-mail"
      >
      <button type="submit" class="btn btn-dark rounded-0" wire:loading.attr="disabled">
        <span wire:loading.remove wire:target="subscribe">S'abonner</span>
        <span wire:loading wire:target="subscribe">…</span>
      </button>
    </form>
    @error('email') <div class="text-danger small mt-2">{{ $message }}</div> @enderror
  @endif
</div>
