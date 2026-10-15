<?php

/**
 * Configuration de l'API du bot WhatsApp (conseillère Chez Lia).
 */
return [

  /*
  | Clé partagée avec la plateforme WhatsApp / la couche IA.
  | Envoyée dans l'en-tête X-Bot-Key. API désactivée si vide.
  */
  'api_key' => env('BOT_API_KEY'),

  /*
  | Remise appliquée quand une commande contient une routine complète
  | (tous les produits d'une gamme). 0 = désactivée tant que la cliente
  | n'a pas validé le pourcentage.
  */
  'kit_discount_percent' => (float) env('BOT_KIT_DISCOUNT_PERCENT', 0),

  /*
  | Durée de validité d'un lien de paiement /payer/{token}, en heures.
  */
  'payment_link_ttl_hours' => (int) env('BOT_PAYMENT_LINK_TTL_HOURS', 72),

  /*
  | Webhook sortant appelé à chaque changement de statut d'une commande
  | (payée, en préparation, expédiée, livrée, annulée) pour prévenir la
  | cliente sur WhatsApp. Vide = pas de notification.
  */
  'notify_url' => env('BOT_NOTIFY_URL'),

  /*
  | Secret de signature HMAC-SHA256 du webhook sortant (en-tête X-Bot-Signature).
  */
  'notify_secret' => env('BOT_NOTIFY_SECRET'),

  /*
  | Modèle WhatsApp (catégorie Utilité) envoyé à l'agent pour une commande
  | « payer en boutique » quand il n'a pas écrit au numéro depuis 24 h.
  | Paramètres du corps : {{1}} n° de commande, {{2}} cliente, {{3}} articles,
  | {{4}} montant. Vide = texte libre seulement.
  */
  'team_order_template' => env('BOT_TEAM_ORDER_TEMPLATE'),
  'team_order_template_lang' => env('BOT_TEAM_ORDER_TEMPLATE_LANG', 'fr'),

  /*
  | Pays de livraison utilisé pour le calcul des tarifs (code ISO).
  */
  'country' => env('BOT_COUNTRY', 'CD'),

  /*
  | Conseillère IA (POST /api/bot/v1/dialogue, appelé par le relais SDev).
  | Clé : ANTHROPIC_API_KEY (config/services.php). Sans clé, chaque message
  | est transféré à l'équipe. La réponse est renvoyée dans le même appel.
  */
  'ai' => [
    'model' => env('BOT_AI_MODEL', 'claude-haiku-4-5-20251001'),
    'max_tokens' => (int) env('BOT_AI_MAX_TOKENS', 1024),
    'max_tool_rounds' => (int) env('BOT_AI_MAX_TOOL_ROUNDS', 6),
    // Au-delà, la conversation repart de zéro (le journal, lui, est conservé).
    'session_ttl_hours' => (int) env('BOT_AI_SESSION_TTL_HOURS', 12),
    'history_limit' => (int) env('BOT_AI_HISTORY_LIMIT', 40),
    'prompt_path' => resource_path('prompts/bot-chez-lia.md'),
    // Attente maximale de POST /api/bot/v1/resultat (compatibilité).
    'poll_seconds' => (float) env('BOT_AI_POLL_SECONDS', 8),
  ],
];
