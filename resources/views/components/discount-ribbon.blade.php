@props([
  'percent' => null,
  'label' => null,
])

@php
  /**
   * Texte court pour le bandeau oblique (ex: -20%).
   */
  $text = $label;

  if ($text === null && $percent !== null) {
    $text = '-' . (int) $percent . '%';
  }

  if ($text === null || $text === '') {
    $text = 'Promo';
  }
@endphp

<span {{ $attributes->merge(['class' => 'discount-ribbon']) }} aria-label="Réduction {{ $text }}">
  {{ $text }}
</span>
