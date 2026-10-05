{{--
  Bouton WhatsApp flottant : ouvre la conversation avec la conseillère Chez Lia
  (bot IA via Callbell). Affiché si un numéro est renseigné dans
  Paramètres > Boutique > WhatsApp.
--}}
@php($whatsappSettings = app(\App\Services\SiteSettingsService::class))
@if ($whatsappSettings->isWhatsappButtonEnabled())
  <a
    href="{{ $whatsappSettings->whatsappUrl('Bonjour Chez Lia 👋 J\'ai une question sur vos produits.') }}"
    class="lia-whatsapp-float"
    target="_blank"
    rel="noopener"
    aria-label="Discuter avec Chez Lia sur WhatsApp"
  >
    <svg width="30" height="30" viewBox="0 0 24 24" aria-hidden="true" fill="currentColor"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2.05 22l5.25-1.38a9.9 9.9 0 0 0 4.74 1.21h.01c5.46 0 9.91-4.45 9.91-9.91C21.96 6.45 17.5 2 12.04 2Zm5.8 14.16c-.25.69-1.44 1.32-1.98 1.37-.51.05-.99.24-3.33-.69-2.81-1.11-4.6-3.98-4.74-4.17-.14-.18-1.13-1.5-1.13-2.87s.72-2.03.97-2.31c.25-.28.55-.35.74-.35l.53.01c.17 0 .4-.06.62.48.25.6.85 2.07.92 2.22.07.15.12.32.02.51-.1.19-.15.31-.29.48-.15.17-.31.38-.44.51-.15.15-.3.31-.13.6.17.29.77 1.27 1.65 2.06 1.13 1.01 2.09 1.32 2.38 1.47.29.15.47.12.64-.07.17-.2.74-.86.94-1.16.2-.29.39-.24.66-.15.27.1 1.72.81 2.02.96.29.15.49.22.56.34.07.12.07.71-.18 1.4Z"/></svg>
    <span class="lia-whatsapp-float__label">Une question ?</span>
  </a>
  <style>
    .lia-whatsapp-float { position:fixed; right:18px; bottom:18px; z-index:9990; display:flex; align-items:center; gap:8px; background:#25D366; color:#fff; border-radius:999px; padding:12px; box-shadow:0 6px 18px rgba(0,0,0,.25); text-decoration:none; }
    .lia-whatsapp-float:hover { color:#fff; background:#1ebe57; }
    .lia-whatsapp-float__label { font-weight:600; padding-right:6px; white-space:nowrap; }
    @media (max-width: 575px) { .lia-whatsapp-float__label { display:none; } }
  </style>
@endif
