<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
  <title>{{ $title ?? 'Lialalionne' }}</title>

  @if (file_exists(public_path('build/manifest.json')))
    @vite(['resources/css/app.css', 'resources/js/app.js'])
  @else
    <link rel="stylesheet" href="{{ asset('css/minimal-pages.css') }}">
  @endif

  @livewireStyles
</head>
<body>
  {{ $slot }}
  @livewireScripts
</body>
</html>
