{{--
  Bloc SEO partagé (canonical, robots, Open Graph, Twitter Card, JSON-LD).
  Variables optionnelles attendues, passées via ->layout('...', [...]) :
  - $title            (déjà géré par ailleurs pour le <title>)
  - $metaDescription  string
  - $canonicalUrl     string (défaut : url()->current())
  - $ogImage          string (défaut : favicon 192)
  - $ogType           string (défaut : 'website', ex. 'product')
  - $noindex          bool (défaut : false — true pour panier/checkout/compte)
  - $jsonLd           array (structured data à sérialiser)
--}}
@php
  $seoTitle = $title ?? 'Lialalionne — Soins corporels';
  $seoImage = $ogImage ?? asset('assets/favicon-192.png');
  $seoUrl = $canonicalUrl ?? url()->current();
@endphp
<link rel="canonical" href="{{ $seoUrl }}">
<meta name="robots" content="{{ !empty($noindex) ? 'noindex, nofollow' : 'index, follow' }}">

<meta property="og:type" content="{{ $ogType ?? 'website' }}">
<meta property="og:site_name" content="Lialalionne">
<meta property="og:locale" content="fr_FR">
<meta property="og:title" content="{{ $seoTitle }}">
@isset($metaDescription)
  <meta property="og:description" content="{{ $metaDescription }}">
@endisset
<meta property="og:url" content="{{ $seoUrl }}">
<meta property="og:image" content="{{ $seoImage }}">

<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $seoTitle }}">
@isset($metaDescription)
  <meta name="twitter:description" content="{{ $metaDescription }}">
@endisset
<meta name="twitter:image" content="{{ $seoImage }}">

@isset($jsonLd)
  <script type="application/ld+json">{!! json_encode($jsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endisset
