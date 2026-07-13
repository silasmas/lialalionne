@props([
  'currentStep' => 1,
  'theme' => 'default',
])

@php
  $steps = [
    1 => ['label' => 'Commande', 'desc' => 'Commande enregistrée'],
    2 => ['label' => 'Envoi', 'desc' => 'Demande envoyée à votre téléphone'],
    3 => ['label' => 'Validation', 'desc' => 'Confirmez sur votre app Mobile Money'],
    4 => ['label' => 'Confirmation', 'desc' => 'Vérification du paiement'],
  ];
  $isShopwise = $theme === 'shopwise';
@endphp

@if ($isShopwise)
  <ol class="list-group list-group-numbered mb-0">
    @foreach ($steps as $stepNumber => $step)
      @php
        $isDone = $currentStep > $stepNumber;
        $isActive = $currentStep === $stepNumber;
      @endphp
      <li class="list-group-item d-flex justify-content-between align-items-start {{ $isDone ? 'list-group-item-success' : '' }} {{ $isActive ? 'payment-step-active' : '' }}">
        <div class="ms-2 me-auto">
          <div class="fw-bold">{{ $step['label'] }}</div>
          {{ $step['desc'] }}
        </div>
        @if ($isDone)
          <span class="badge bg-success rounded-pill">OK</span>
        @elseif ($isActive)
          <span class="badge payment-step-badge rounded-pill">{{ $stepNumber }}</span>
        @endif
      </li>
    @endforeach
  </ol>
@else
  <ol class="mb-8 grid grid-cols-2 gap-4 sm:grid-cols-4">
    @foreach ($steps as $stepNumber => $step)
      @php
        $isDone = $currentStep > $stepNumber;
        $isActive = $currentStep === $stepNumber;
      @endphp
      <li class="relative rounded-lg border p-3 {{ $isDone ? 'border-green-500 bg-green-50' : ($isActive ? 'border-brand-600 bg-brand-50' : 'border-stone-200 bg-white') }}">
        <div class="flex items-center gap-2">
          <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $isDone ? 'bg-green-600 text-white' : ($isActive ? 'bg-brand-600 text-white' : 'bg-stone-200 text-stone-600') }}">
            @if ($isDone)
              ✓
            @else
              {{ $stepNumber }}
            @endif
          </span>
          <span class="text-sm font-semibold text-stone-900">{{ $step['label'] }}</span>
        </div>
        <p class="mt-2 text-xs text-stone-600">{{ $step['desc'] }}</p>
      </li>
    @endforeach
  </ol>
@endif
