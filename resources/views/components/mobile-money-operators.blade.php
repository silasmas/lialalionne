@props([
  'selectedOperator' => 'mpesa',
  'phone' => '',
  'phoneField' => 'mobileMoneyPhone',
  'operatorField' => 'mobileMoneyOperator',
  'disabled' => false,
  'theme' => 'default',
])

@php
  $operators = \App\Enums\MobileMoneyOperator::all();
  $current = \App\Enums\MobileMoneyOperator::tryFrom($selectedOperator) ?? \App\Enums\MobileMoneyOperator::Mpesa;
  $isShopwise = $theme === 'shopwise';
@endphp

@if ($isShopwise)
  <div class="border rounded p-3 mb-3">
    <p class="mb-3 fw-semibold">Opérateur Mobile Money</p>
    <div class="row g-2 mb-3">
      @foreach ($operators as $operator)
        <div class="col-6 col-md-3">
          <div class="custome-radio">
            <input
              class="form-check-input"
              type="radio"
              wire:model.live="{{ $operatorField }}"
              value="{{ $operator->value }}"
              id="mmOperator{{ $operator->value }}"
              @disabled($disabled)
            >
            <label class="form-check-label" for="mmOperator{{ $operator->value }}">{{ $operator->label() }}</label>
          </div>
        </div>
      @endforeach
    </div>
    @error($operatorField) <div class="text-danger small mb-2">{{ $message }}</div> @enderror

    <div class="form-group mb-0">
      <input
        type="tel"
        wire:model.live="{{ $phoneField }}"
        placeholder="{{ $current->placeholder() }}"
        @disabled($disabled)
        class="form-control @error($phoneField) is-invalid @enderror"
      >
      <small class="text-muted">Le numéro doit commencer par 243 (ex. {{ $current->placeholder() }}).</small>
      @error($phoneField) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
  </div>
@else
  <div {{ $attributes->merge(['class' => 'space-y-4 rounded-lg border border-stone-200 bg-stone-50 p-4']) }}>
    <p class="text-sm font-medium text-stone-900">Opérateur Mobile Money</p>

    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
      @foreach ($operators as $operator)
        <label class="cursor-pointer rounded-lg border bg-white p-3 text-center transition {{ $selectedOperator === $operator->value ? 'border-brand-600 ring-2 ring-brand-100' : 'border-stone-200 hover:border-brand-300' }}">
          <input
            type="radio"
            wire:model.live="{{ $operatorField }}"
            value="{{ $operator->value }}"
            class="sr-only"
            @disabled($disabled)
          />
          <span class="block text-sm font-semibold text-stone-900">{{ $operator->label() }}</span>
          <span class="mt-1 block text-xs text-stone-500">{{ implode(', ', $operator->prefixes()) }}</span>
        </label>
      @endforeach
    </div>
    @error($operatorField) <p class="text-xs text-red-600">{{ $message }}</p> @enderror

    <div>
      <label for="mobileMoneyPhone" class="mb-1 block text-sm font-medium text-stone-700">
        Numéro {{ $current->label() }}
      </label>
      <input
        id="mobileMoneyPhone"
        type="tel"
        wire:model.live="{{ $phoneField }}"
        placeholder="{{ $current->placeholder() }}"
        @disabled($disabled)
        class="w-full rounded-lg border border-stone-300 px-3 py-2 text-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 disabled:bg-stone-100"
      />
      <p class="mt-1 text-xs text-stone-500">Le numéro doit commencer par 243 (ex. {{ $current->placeholder() }}).</p>
      @error($phoneField) <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
    </div>
  </div>
@endif
