@php
  $metaPixelId = config('services.meta_pixel.id');
@endphp
@if ($metaPixelId)
  <script>
    (function () {
      var PIXEL_ID = @json($metaPixelId);
      var initialized = false;

      /**
       * Vérifie si le visiteur a accepté les cookies de mesure d'audience.
       *
       * @return {boolean} True si consentement analytics donné
       */
      function hasAnalyticsConsent() {
        try {
          var consent = window.lialalionneCookieConsent && window.lialalionneCookieConsent.read();

          return !!(consent && consent.analytics);
        } catch (error) {
          return false;
        }
      }

      /**
       * Charge le SDK Meta Pixel et envoie le premier PageView.
       *
       * @return void
       */
      function loadPixel() {
        if (initialized || !hasAnalyticsConsent()) {
          return;
        }

        initialized = true;

        !function (f, b, e, v, n, t, s) {
          if (f.fbq) return;
          n = f.fbq = function () {
            n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments);
          };
          if (!f._fbq) f._fbq = n;
          n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
          t = b.createElement(e); t.async = !0; t.src = v;
          s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s);
        }(window, document, 'script', 'https://connect.facebook.net/en_US/fbevents.js');

        fbq('init', PIXEL_ID);
        fbq('track', 'PageView');
      }

      /**
       * Envoie un PageView pour chaque navigation Livewire (SPA sans reload).
       *
       * @return void
       */
      function trackNavigation() {
        if (initialized && window.fbq) {
          fbq('track', 'PageView');
        } else {
          loadPixel();
        }
      }

      document.addEventListener('DOMContentLoaded', loadPixel);
      document.addEventListener('livewire:navigated', trackNavigation);
      window.addEventListener('cookie-consent-updated', loadPixel);
    })();
  </script>
  <noscript>
    <img height="1" width="1" style="display:none" alt=""
      src="https://www.facebook.com/tr?id={{ $metaPixelId }}&ev=PageView&noscript=1">
  </noscript>
@endif
