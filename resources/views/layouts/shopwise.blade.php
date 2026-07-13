<!DOCTYPE html>
<html lang="fr">
<head>
  @include('layouts.partials.shopwise.head-styles')
</head>
<body>

<div class="preloader">
  <div class="lds-ellipsis">
    <span></span>
    <span></span>
    <span></span>
  </div>
</div>

@include('layouts.partials.shopwise.header')

<livewire:shop.toast-notifier />

{{ $slot }}

@include('layouts.partials.shopwise.newsletter')
@include('layouts.partials.shopwise.footer')
<x-cookie-consent />
@include('layouts.partials.shopwise.scripts')
</body>
</html>
