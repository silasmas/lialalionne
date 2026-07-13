<div class="me-3">
  <select wire:model.live="currency" class="custome_select" aria-label="Devise">
    @foreach ($currencies as $code)
      <option value="{{ $code }}">{{ $code }}</option>
    @endforeach
  </select>
</div>
