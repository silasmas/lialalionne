<div class="shopwise-toast-stack" aria-live="polite" aria-atomic="true">
  @if ($visible && $message)
    <div
      wire:key="shopwise-toast-{{ $toastId }}"
      x-data="{ show: true }"
      x-init="setTimeout(() => { show = false; $wire.hideToast(); }, 4000)"
      x-show="show"
      class="shopwise-toast is-visible is-{{ $type === 'error' ? 'error' : 'success' }}"
      role="alert"
    >
      {{ $message }}
    </div>
  @endif
</div>
