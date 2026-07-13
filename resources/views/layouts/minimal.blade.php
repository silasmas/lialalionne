<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $title ?? 'Lialalionne' }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
  <style>
    body {
      margin: 0;
      min-height: 100vh;
      background: #0a0a0a;
      color: #f5f5f5;
      font-family: Poppins, ui-sans-serif, system-ui, sans-serif;
    }

    .install-logo {
      max-height: 48px;
      width: auto;
    }
  </style>
</head>
<body>
  {{ $slot }}
  @livewireScripts
</body>
</html>
