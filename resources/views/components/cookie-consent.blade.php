@php
  $consentKey = 'lialalionne_cookie_consent_v1';
  $privacyUrl = route('legal.show', 'confidentialite');
@endphp

<div
  id="cookieConsentBanner"
  class="cookie-consent"
  role="dialog"
  aria-live="polite"
  aria-label="Consentement cookies"
  hidden
>
  <div class="cookie-consent__inner">
    <div class="cookie-consent__content">
      <p class="cookie-consent__title">Cookies & confidentialité</p>
      <p class="cookie-consent__text">
        Nous utilisons des cookies <strong>essentiels</strong> pour le panier, la session et la sécurité.
        Les cookies de mesure d'audience ou publicitaires ne sont activés qu'avec votre accord.
        <a href="{{ $privacyUrl }}" class="cookie-consent__link">En savoir plus</a>
      </p>
    </div>
    <div class="cookie-consent__actions">
      <button type="button" class="cookie-consent__btn cookie-consent__btn--secondary" data-cookie-consent="reject">
        Refuser les cookies optionnels
      </button>
      <button type="button" class="cookie-consent__btn cookie-consent__btn--primary" data-cookie-consent="accept">
        Tout accepter
      </button>
    </div>
  </div>
</div>

<script>
  (function () {
    var STORAGE_KEY = @json($consentKey);
    var COOKIE_NAME = 'lialalionne_cookie_consent';
    var COOKIE_MAX_AGE = 60 * 60 * 24 * 180;

    /**
     * Lit le consentement enregistré dans le navigateur.
     *
     * @return {object|null} Préférences cookies ou null
     */
    function readConsent() {
      try {
        var raw = localStorage.getItem(STORAGE_KEY);

        if (!raw) {
          return null;
        }

        return JSON.parse(raw);
      } catch (error) {
        return null;
      }
    }

    /**
     * Enregistre le consentement cookies côté navigateur.
     *
     * @param {boolean} analytics Cookies de mesure d'audience autorisés
     * @return {object} Préférences enregistrées
     */
    function saveConsent(analytics) {
      var payload = {
        essential: true,
        analytics: analytics === true,
        updatedAt: new Date().toISOString()
      };

      localStorage.setItem(STORAGE_KEY, JSON.stringify(payload));
      document.cookie = COOKIE_NAME + '=' + (analytics ? 'all' : 'essential')
        + '; path=/; max-age=' + COOKIE_MAX_AGE + '; SameSite=Lax';

      window.dispatchEvent(new CustomEvent('cookie-consent-updated', { detail: payload }));

      return payload;
    }

    /**
     * Affiche ou masque le bandeau cookies.
     *
     * @param {boolean} visible True pour afficher le bandeau
     * @return void
     */
    function toggleBanner(visible) {
      var banner = document.getElementById('cookieConsentBanner');

      if (!banner) {
        return;
      }

      if (visible) {
        banner.hidden = false;
        banner.classList.add('is-visible');
      } else {
        banner.classList.remove('is-visible');
        banner.hidden = true;
      }
    }

    /**
     * Initialise les boutons et l'affichage du bandeau cookies.
     *
     * @return void
     */
    function initCookieConsent() {
      var banner = document.getElementById('cookieConsentBanner');

      if (!banner || banner.dataset.initialized === '1') {
        return;
      }

      banner.dataset.initialized = '1';

      if (!readConsent()) {
        toggleBanner(true);
      }

      banner.querySelectorAll('[data-cookie-consent]').forEach(function (button) {
        button.addEventListener('click', function () {
          var choice = button.getAttribute('data-cookie-consent');
          saveConsent(choice === 'accept');
          toggleBanner(false);
        });
      });
    }

    /**
     * Rouvre le bandeau depuis le lien « Gérer les cookies ».
     *
     * @param {Event} event Événement clic
     * @return void
     */
    function openCookiePreferences(event) {
      if (event) {
        event.preventDefault();
      }

      toggleBanner(true);
    }

    document.addEventListener('DOMContentLoaded', initCookieConsent);
    document.addEventListener('livewire:navigated', initCookieConsent);
    document.addEventListener('click', function (event) {
      var trigger = event.target.closest('[data-open-cookie-consent]');

      if (trigger) {
        openCookiePreferences(event);
      }
    });

    window.lialalionneCookieConsent = {
      read: readConsent,
      open: function () {
        toggleBanner(true);
      }
    };
  })();
</script>
