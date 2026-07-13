<select
  wire:model.live="currency"
  class="rounded-lg border border-stone-300 bg-white px-2 py-1.5 text-sm text-stone-700 focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"
  aria-label="Devise"
>
  @foreach ($currencies as $code)
    <option value="{{ $code }}">{{ $code }}</option>
  @endforeach
</select>
