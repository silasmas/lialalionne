<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}">
  <link rel="shortcut icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
  <title>{{ $title ?? 'Installation — Lialalionne' }}</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
  {{-- Feuille autonome : /install doit rester lisible sans npm / Vite. --}}
  <link rel="stylesheet" href="{{ asset('css/minimal-pages.css') }}?v={{ @filemtime(public_path('css/minimal-pages.css')) ?: '1' }}">
</head>
<body class="install-body">
  @yield('content')
</body>
</html>
