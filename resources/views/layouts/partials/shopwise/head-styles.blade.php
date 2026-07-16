<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'Lialalionne — Soins corporels' }}</title>
@isset($metaDescription)
  <meta name="description" content="{{ $metaDescription }}">
@endisset

<link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32.png') }}">
<link rel="icon" type="image/png" sizes="192x192" href="{{ asset('assets/favicon-192.png') }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ asset('assets/apple-touch-icon.png') }}">
<link rel="shortcut icon" type="image/png" href="{{ asset('assets/favicon.png') }}">
<link rel="stylesheet" href="{{ $sw('css/animate.css') }}">
<link rel="stylesheet" href="{{ $sw('bootstrap/css/bootstrap.min.css') }}">
<link href="https://fonts.googleapis.com/css?family=Roboto:100,300,400,500,700,900&display=swap" rel="stylesheet">
<link href="https://fonts.googleapis.com/css?family=Poppins:200,300,400,500,600,700,800,900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="{{ $sw('css/all.min.css') }}">
<link rel="stylesheet" href="{{ $sw('css/ionicons.min.css') }}">
<link rel="stylesheet" href="{{ $sw('css/themify-icons.css') }}">
<link rel="stylesheet" href="{{ $sw('css/linearicons.css') }}">
<link rel="stylesheet" href="{{ $sw('css/flaticon.css') }}">
<link rel="stylesheet" href="{{ $sw('css/simple-line-icons.css') }}">
<link rel="stylesheet" href="{{ $sw('owlcarousel/css/owl.carousel.min.css') }}">
<link rel="stylesheet" href="{{ $sw('owlcarousel/css/owl.theme.css') }}">
<link rel="stylesheet" href="{{ $sw('owlcarousel/css/owl.theme.default.min.css') }}">
<link rel="stylesheet" href="{{ $sw('css/magnific-popup.css') }}">
<link rel="stylesheet" href="{{ $sw('css/slick.css') }}">
<link rel="stylesheet" href="{{ $sw('css/slick-theme.css') }}">
<link rel="stylesheet" href="{{ $sw('css/style.css') }}">
<link rel="stylesheet" href="{{ $sw('css/responsive.css') }}">
<style>
  .preloader {
    transition: opacity 0.35s ease, visibility 0.35s ease;
  }

  .preloader.is-hidden {
    opacity: 0 !important;
    visibility: hidden !important;
    pointer-events: none !important;
  }

  .cart_box .item_remove {
    cursor: pointer;
  }

  .cart_box .cart_buttons {
    display: flex;
    flex-direction: row;
    flex-wrap: nowrap;
    gap: 8px;
    align-items: stretch;
  }

  .cart_box .cart_buttons .btn {
    flex: 1 1 0;
    min-width: 0;
    white-space: nowrap;
    font-size: 12px;
    padding: 8px 10px !important;
  }

  .shopwise-mfp-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.75);
    z-index: 1040;
  }

  .shopwise-mfp-open {
    position: fixed;
    inset: 0;
    z-index: 1050;
    overflow-x: hidden;
    overflow-y: auto;
    outline: 0;
  }

  .shopwise-mfp-open .mfp-container {
    padding: 24px 12px;
    min-height: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .shopwise-mfp-open .white-popup {
    position: relative;
    background: #fff;
    width: 100%;
    max-width: 980px;
    margin: 0 auto;
    padding: 24px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.25);
  }

  .shopwise-mfp-open .mfp-close {
    position: absolute;
    top: 8px;
    right: 8px;
    border: 0;
    background: transparent;
    font-size: 28px;
    line-height: 1;
    z-index: 2;
    cursor: pointer;
  }

    .shopwise-mfp-open.mfp-ajax-holder .white-popup {
      max-width: 1100px;
    }

    .shopwise-toast-stack {
      position: fixed;
      top: 88px;
      right: 16px;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 10px;
      max-width: min(360px, calc(100vw - 32px));
      pointer-events: none;
    }

    .shopwise-toast {
      pointer-events: auto;
      padding: 14px 18px;
      border-radius: 4px;
      color: #fff;
      font-size: 14px;
      line-height: 1.45;
      box-shadow: 0 8px 24px rgba(0, 0, 0, 0.18);
      opacity: 0;
      transform: translateX(24px);
      transition: opacity 0.3s ease, transform 0.3s ease;
    }

    .shopwise-toast.is-visible {
      opacity: 1;
      transform: translateX(0);
    }

    .shopwise-toast.is-success {
      background-color: #28a745;
    }

    .shopwise-toast.is-error {
      background-color: #dc3545;
    }

    .lw-action {
      position: relative;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
      vertical-align: middle;
    }

    .lw-action--loading {
      pointer-events: none;
      cursor: wait;
      opacity: 0.85;
    }

    .lw-action__loader {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
    }

    .lw-action__loading-text {
      font-size: inherit;
      line-height: 1.2;
    }

    .lw-spinner {
      display: inline-block;
      border: 2px solid currentColor;
      border-right-color: transparent;
      border-radius: 50%;
      animation: lw-spin 0.65s linear infinite;
      flex-shrink: 0;
    }

    .lw-spinner--sm {
      width: 1rem;
      height: 1rem;
    }

    .lw-spinner--md {
      width: 1.25rem;
      height: 1.25rem;
    }

    @keyframes lw-spin {
      to {
        transform: rotate(360deg);
      }
    }

    .product_action_box .lw-action,
    .list_product_action_box .lw-action {
      min-width: 2rem;
      min-height: 2rem;
    }

    .quantity .lw-action.minus,
    .quantity .lw-action.plus {
      width: auto;
      min-width: 2.25rem;
      padding: 0;
      border: 0;
      background: transparent;
      font: inherit;
      color: inherit;
    }

    .cookie-consent {
      position: fixed;
      left: 0;
      right: 0;
      bottom: 0;
      z-index: 10050;
      padding: 16px;
      transform: translateY(110%);
      transition: transform 0.35s ease;
      pointer-events: none;
    }

    .cookie-consent.is-visible {
      transform: translateY(0);
      pointer-events: auto;
    }

    .cookie-consent__inner {
      max-width: 980px;
      margin: 0 auto;
      padding: 18px 20px;
      border-radius: 8px;
      background: #fff;
      box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
      border: 1px solid rgba(0, 0, 0, 0.06);
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      justify-content: space-between;
      gap: 16px;
    }

    .cookie-consent__content {
      flex: 1 1 320px;
    }

    .cookie-consent__title {
      margin: 0 0 6px;
      font-weight: 600;
      color: #222;
    }

    .cookie-consent__text {
      margin: 0;
      font-size: 14px;
      line-height: 1.55;
      color: #555;
    }

    .cookie-consent__link {
      color: #c5a059;
      text-decoration: underline;
    }

    .cookie-consent__actions {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      flex: 0 0 auto;
    }

    .cookie-consent__btn {
      border: 0;
      border-radius: 4px;
      padding: 10px 16px;
      font-size: 13px;
      font-weight: 600;
      line-height: 1.2;
      cursor: pointer;
      white-space: nowrap;
      transition: opacity 0.2s ease;
    }

    .cookie-consent__btn:hover {
      opacity: 0.9;
    }

    .cookie-consent__btn--primary {
      background: #c5a059;
      color: #fff;
    }

    .navbar-brand img,
    .footer_logo img {
      width: 72px;
      height: 72px;
      max-height: none;
      object-fit: cover;
      border-radius: 50%;
      display: block;
    }

    .pagination .page-item a,
    .pagination .page-link {
      color: #687188;
    }

    .pagination .page-item.active .page-link,
    .pagination .page-link:hover,
    .pagination .page-item.active a {
      background-color: #C5A059;
      border-color: #C5A059;
      color: #fff;
    }

    .payment-step-active {
      background-color: #fef9e7;
      border-color: #c5a059;
      color: #734f08;
    }

    .payment-step-badge {
      background-color: #c5a059;
      color: #fff;
    }

    .mm-payment-status {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 6px;
      background: #fef9e7;
      border: 1px solid #f5d061;
      color: #734f08;
      font-size: 14px;
      line-height: 1.45;
    }

    .mm-payment-status__spinner {
      display: inline-block;
      width: 16px;
      height: 16px;
      border: 2px solid #c5a059;
      border-right-color: transparent;
      border-radius: 50%;
      animation: mm-spin 0.65s linear infinite;
      flex-shrink: 0;
    }

    @keyframes mm-spin {
      to {
        transform: rotate(360deg);
      }
    }

    .cookie-consent__btn--secondary {
      background: #fff;
      color: #000;
      border: 1px solid #000;
      border-radius: 40px;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .cookie-consent__btn--secondary:hover {
      background: #000;
      color: #fff;
    }

    /* Boutons style OS Body : pilule + letter-spacing, or logo + noir profond */
    .btn,
    button.btn,
    a.btn,
    .btn-fill-out,
    .btn-border-fill,
    .btn-fill-line,
    .btn-line-fill,
    .btn-fill-out-dark {
      border-radius: 40px !important;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 500;
      font-size: 13px;
      padding: 13px 32px !important;
      transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease, opacity 0.2s ease;
    }

    .btn-fill-out {
      background-color: #C5A059 !important;
      border: 1px solid #C5A059 !important;
      color: #000000 !important;
      overflow: hidden;
    }

    .btn-fill-out::before,
    .btn-fill-out::after {
      display: none !important;
    }

    .btn-fill-out:hover,
    .btn-fill-out:focus {
      background-color: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    .btn-border-fill {
      background-color: transparent !important;
      border: 1px solid #000000 !important;
      color: #000000 !important;
    }

    .btn-border-fill::before,
    .btn-border-fill::after {
      display: none !important;
    }

    .btn-border-fill:hover,
    .btn-border-fill:focus {
      background-color: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    .btn-fill-line,
    .btn-fill-out-dark {
      background-color: #000000 !important;
      border: 1px solid #000000 !important;
      color: #ffffff !important;
    }

    .btn-fill-line::before,
    .btn-fill-line::after,
    .btn-fill-out-dark::before {
      display: none !important;
    }

    .btn-fill-line:hover,
    .btn-fill-out-dark:hover {
      background-color: #C5A059 !important;
      border-color: #C5A059 !important;
      color: #000000 !important;
    }

    .btn-white {
      border-radius: 40px !important;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      border-color: #ffffff !important;
      color: #ffffff !important;
      background-color: transparent !important;
    }

    .btn-white::before,
    .btn-white::after {
      display: none !important;
    }

    .btn-white:hover {
      background-color: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    /* Tout bouton noir : texte blanc clair */
    .btn.bg-dark,
    .btn-dark,
    a.btn[style*="background"] {
      color: #ffffff !important;
    }

    body,
    h1, h2, h3, h4, h5, h6,
    .navbar-nav .nav-link,
    .header_wrap {
      color: #000000;
    }

    .top-header,
    .bottom_footer,
    .footer_dark,
    .bg_dark {
      background-color: #000000 !important;
    }

    a:hover,
    .text_default,
    .product_title a:hover,
    .navbar-nav .nav-item .nav-link.active,
    .navbar-nav .nav-item:hover > .nav-link {
      color: #C5A059 !important;
    }

    .cookie-consent__btn--primary {
      background: #C5A059;
      color: #000;
      border-radius: 40px;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .cookie-consent__btn--secondary {
      border-radius: 40px;
      letter-spacing: 0.06em;
      text-transform: uppercase;
    }

    .cookie-consent__link {
      color: #C5A059;
    }

    .pagination .page-item.active .page-link,
    .pagination .page-link:hover,
    .pagination .page-item.active a {
      background-color: #C5A059;
      border-color: #C5A059;
      color: #000;
    }

    .payment-step-badge {
      background-color: #C5A059;
      color: #000;
    }

    /* Boutique : tri, liste, pagination, focus (or + noir, pas de bleu) */
    .form-control,
    .form-control-sm,
    .custom_select select,
    .custom_select select.form-control-sm,
    select.form-control {
      color: #000000 !important;
      border-color: #000000 !important;
      background-color: #ffffff !important;
      box-shadow: none !important;
      outline: none !important;
      accent-color: #C5A059;
    }

    .form-control:focus,
    .form-control-sm:focus,
    .custom_select select:focus,
    .custom_select select.form-control-sm:focus,
    select.form-control:focus,
    .form-select:focus {
      color: #000000 !important;
      border-color: #C5A059 !important;
      background-color: #ffffff !important;
      box-shadow: 0 0 0 0.2rem rgba(197, 160, 89, 0.35) !important;
      outline: none !important;
    }

    .custom_select::before {
      color: #C5A059 !important;
    }

    .shorting_icon {
      border-color: #000000 !important;
      color: #000000 !important;
      border-radius: 8px !important;
    }

    .shorting_icon:hover {
      background-color: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    .shorting_icon.active {
      background-color: #C5A059 !important;
      border-color: #C5A059 !important;
      color: #000000 !important;
    }

    .shorting_icon.active:hover {
      background-color: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    .pr_action_btn li a:hover,
    .product_action_box .pr_action_btn li a:hover {
      background-color: #000000 !important;
      color: #ffffff !important;
    }

    .shop_container.list .list_product_action_box .pr_action_btn li.add-to-cart a,
    .shop_container.list .pr_action_btn li.add-to-cart a {
      font-size: 13px !important;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 500;
      width: auto !important;
      height: auto !important;
      padding: 12px 28px !important;
      background-color: #C5A059 !important;
      border: 1px solid #C5A059 !important;
      color: #000000 !important;
      border-radius: 40px !important;
      transition: background-color 0.25s ease, color 0.25s ease, border-color 0.25s ease;
    }

    .shop_container.list .list_product_action_box .pr_action_btn li.add-to-cart a:hover,
    .shop_container.list .pr_action_btn li.add-to-cart a:hover,
    .shop_container.list .pr_action_btn li.add-to-cart a:focus {
      background-color: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    .shop_container.list .pr_action_btn li:not(.add-to-cart) a:hover {
      background-color: #000000 !important;
      color: #ffffff !important;
    }

    .btn-outline-dark,
    .btn-dark {
      border-radius: 40px !important;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      font-weight: 500;
    }

    .btn-outline-dark {
      background: transparent !important;
      border: 1px solid #000000 !important;
      color: #000000 !important;
    }

    .btn-outline-dark:hover,
    .btn-outline-dark:focus {
      background: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    .btn-dark {
      background: #000000 !important;
      border: 1px solid #000000 !important;
      color: #ffffff !important;
    }

    .btn-dark:hover,
    .btn-dark:focus {
      background: #C5A059 !important;
      border-color: #C5A059 !important;
      color: #000000 !important;
    }

    .pagination .page-link,
    .pagination_style1 .page-item a,
    .pagination .page-item .page-link {
      color: #000000 !important;
      border-color: #000000 !important;
      background: #ffffff !important;
      border-radius: 8px !important;
    }

    .pagination .page-item.active .page-link,
    .pagination .page-link:hover,
    .pagination .page-item.active a,
    .pagination_style1 .page-item.active .page-link,
    .pagination_style1 .page-item .page-link:hover {
      background-color: #C5A059 !important;
      border-color: #C5A059 !important;
      color: #000000 !important;
      box-shadow: none !important;
    }

    .pagination .page-item:not(.active) .page-link:hover,
    .pagination_style1 .page-item:not(.active) .page-link:hover {
      background-color: #000000 !important;
      border-color: #000000 !important;
      color: #ffffff !important;
    }

    .pagination .page-link:focus {
      box-shadow: 0 0 0 0.2rem rgba(197, 160, 89, 0.35) !important;
      border-color: #C5A059 !important;
      color: #000000 !important;
    }

    .newsletter_form .btn-dark:hover {
      background: #C5A059 !important;
      color: #000000 !important;
    }

    .discount-ribbon {
      position: absolute;
      top: 18px;
      left: -36px;
      z-index: 6;
      width: 130px;
      padding: 7px 0;
      background-color: #C5A059;
      color: #000000;
      font-size: 12px;
      font-weight: 700;
      letter-spacing: 0.08em;
      line-height: 1.2;
      text-align: center;
      text-transform: uppercase;
      transform: rotate(-45deg);
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.25);
      pointer-events: none;
      white-space: nowrap;
    }

    .product_img,
    .product_img_box {
      overflow: hidden;
      position: relative;
    }

    /* Modale d'accueil — produits vedettes */
    .featured-welcome-popup .modal-content {
      border: 0;
      border-radius: 0;
      overflow: hidden;
    }

    .featured-welcome-media {
      position: relative;
      min-height: 320px;
      height: 100%;
      background: #000000;
      overflow: hidden;
    }

    .featured-welcome-image {
      width: 100%;
      height: 100%;
      min-height: 320px;
      object-fit: cover;
      display: block;
    }

    .featured-welcome-content {
      text-align: left;
      padding: 40px 36px;
    }

    .featured-welcome-eyebrow {
      color: #C5A059;
      font-size: 12px;
      letter-spacing: 0.2em;
      font-weight: 600;
      margin: 0;
    }

    .featured-welcome-category {
      color: #687188;
      font-size: 13px;
      margin: 0;
    }

    .featured-welcome-price .price {
      font-size: 1.35rem;
      color: #C5A059;
      font-weight: 700;
      margin-right: 8px;
    }

    .featured-welcome-price del {
      color: #919191;
      font-size: 0.95rem;
    }

    .featured-welcome-carousel .carousel-indicators {
      margin-bottom: 0.75rem;
    }

    .featured-welcome-carousel .carousel-indicators [data-bs-target] {
      width: 10px;
      height: 10px;
      border-radius: 50%;
      background-color: #C5A059;
      opacity: 0.35;
    }

    .featured-welcome-carousel .carousel-indicators .active {
      opacity: 1;
      background-color: #000000;
    }

    .featured-welcome-carousel .carousel-control-prev,
    .featured-welcome-carousel .carousel-control-next {
      width: 8%;
      opacity: 0.85;
    }

    .featured-welcome-carousel .carousel-control-prev-icon,
    .featured-welcome-carousel .carousel-control-next-icon {
      filter: none;
      background-color: #000000;
      border-radius: 50%;
      background-size: 50% 50%;
      width: 2rem;
      height: 2rem;
    }

    @media (max-width: 575px) {
      .featured-welcome-content {
        padding: 28px 20px 36px;
        text-align: center;
      }

      .featured-welcome-media,
      .featured-welcome-image {
        min-height: 220px;
      }
    }

    @media (max-width: 575px) {
      .cookie-consent__actions {
        width: 100%;
      }

      .cookie-consent__actions .cookie-consent__btn {
        flex: 1 1 auto;
      }
    }
  </style>
@livewireStyles
