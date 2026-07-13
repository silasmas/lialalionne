@props([
  'action',
  'tag' => 'button',
  'href' => '#',
  'prevent' => true,
  'stop' => false,
  'confirm' => null,
  'loadingLabel' => null,
  'loaderSize' => 'sm',
])

@php
  $baseClass = 'lw-action';
  $mergedClass = trim($baseClass . ' ' . ($attributes->get('class') ?? ''));
  $clickPrefix = $stop ? 'wire:click.stop' : 'wire:click';
  $clickSuffix = $prevent ? '.prevent' : '';
  $clickAttribute = $clickPrefix . $clickSuffix . '="' . $action . '"';
@endphp

@if ($tag === 'a')
  <a
    href="{{ $href }}"
    {!! $clickAttribute !!}
    @if ($confirm) wire:confirm="{{ $confirm }}" @endif
    wire:loading.attr="disabled"
    wire:target="{{ $action }}"
    wire:loading.class="lw-action--loading"
    {{ $attributes->merge(['class' => $mergedClass]) }}
  >
    <span class="lw-action__content" wire:loading.remove wire:target="{{ $action }}">
      {{ $slot }}
    </span>
    <span class="lw-action__loader" wire:loading wire:target="{{ $action }}" aria-hidden="true">
      <span class="lw-spinner lw-spinner--{{ $loaderSize }}"></span>
      @if ($loadingLabel)
        <span class="lw-action__loading-text">{{ $loadingLabel }}</span>
      @endif
    </span>
  </a>
@else
  <button
    type="button"
    {!! $clickAttribute !!}
    @if ($confirm) wire:confirm="{{ $confirm }}" @endif
    wire:loading.attr="disabled"
    wire:target="{{ $action }}"
    wire:loading.class="lw-action--loading"
    {{ $attributes->merge(['class' => $mergedClass]) }}
  >
    <span class="lw-action__content" wire:loading.remove wire:target="{{ $action }}">
      {{ $slot }}
    </span>
    <span class="lw-action__loader" wire:loading wire:target="{{ $action }}" aria-hidden="true">
      <span class="lw-spinner lw-spinner--{{ $loaderSize }}"></span>
      @if ($loadingLabel)
        <span class="lw-action__loading-text">{{ $loadingLabel }}</span>
      @endif
    </span>
  </button>
@endif
