<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $title ?? 'Lialalionne — Soins corporels' }}</title>
@isset($metaDescription)
  <meta name="description" content="{{ $metaDescription }}">
@endisset

<link rel="shortcut icon" type="image/x-icon" href="{{ $sw('images/favicon.png') }}">
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
      max-height: 72px;
      width: auto;
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
      color: #333;
      border: 1px solid #ddd;
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
