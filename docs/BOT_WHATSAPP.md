# Bot WhatsApp — Chez Lia / Lialalionne

La conseillère Chez Lia répond aux clientes sur WhatsApp. Même architecture que le bot Rachoux Traiteur : **Callbell ne fait que relayer chaque message** vers le site, et toute l'intelligence vit dans Laravel. La différence : ici, c'est une IA (Claude) qui comprend les messages libres, avec des outils branchés sur la boutique.

```
Cliente WhatsApp → Callbell → POST /api/bot/v1/dialogue → BotAiAgent (Claude + outils) → {etat, text} → Callbell → Cliente
                                                                     ↓
                                     catalogue, devis, commande, push Mobile Money, suivi (BotCommerceService)
Site (FlexPay confirme, admin expédie…) → BotNotifier → Callbell → message de suivi à la cliente
```

## 1. Configuration (.env)

| Variable | Rôle |
| --- | --- |
| `BOT_API_KEY` | Clé secrète choisie par nous. Callbell l'envoie dans l'URL (`?bot_token=`) ou l'en-tête `X-Bot-Key`. Vide = API désactivée (503). |
| `ANTHROPIC_API_KEY` | Clé de l'API Claude (console.anthropic.com). Sans clé, chaque message est transféré à l'équipe. |
| `BOT_AI_MODEL` | Modèle Claude. Défaut `claude-haiku-4-5-20251001` (le moins cher). Plus fin mais 2× plus cher : `claude-sonnet-5-5`. |
| `BOT_AI_MAX_TOKENS` | Longueur maximale d'une réponse (défaut 1024). |
| `BOT_AI_SESSION_TTL_HOURS` | Après cette pause, la conversation repart de zéro (défaut 12 h). Le journal est conservé. |
| `CALLBELL_API_TOKEN`, `CALLBELL_CHANNEL_UUID` | Envoi des réponses de l'IA (mode différé) et des messages de suivi (paiement reçu, expédiée, livrée…). |
| `CALLBELL_TEAM_UUID` | Équipe Callbell à qui assigner une conversation transférée par l'IA (mode différé). |
| `BOT_AI_ASYNC` | `true` (défaut) : réponse différée via l'API Callbell, pour ne plus dépasser les 10 s du webhook. Ignoré si `CALLBELL_API_TOKEN` / `CALLBELL_CHANNEL_UUID` sont vides. |
| `BOT_KIT_DISCOUNT_PERCENT` | Remise quand une routine complète est commandée. 0 = désactivée. |
| `BOT_PAYMENT_LINK_TTL_HOURS` | Validité du lien de paiement par carte (défaut 72). |
| `BOT_NOTIFY_URL`, `BOT_NOTIFY_SECRET` | Optionnel : webhook sortant signé à chaque changement de statut. |

Générer `BOT_API_KEY` :

```bash
php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
```

Mise en route serveur :

```bash
php artisan migrate --force      # orders.source / payment_token, bot_conversations, bot_messages
php artisan config:clear
```

## 2. Flux Callbell (copie du flux Rachoux v4)

Compte Callbell SDev (le même que Rachoux, CMP, CEPD) :
- Canal WhatsApp Cloud API **Lia la Lionne** — numéro +243 965 822 818, UUID `9e31e92ad5ef4cedbb09e8a3e09cee5b` (`CALLBELL_CHANNEL_UUID`), id 202511.
- Boîte de réception de l'équipe **LIA LA LIONNE** (id 81834) — y ajouter au moins un membre, sinon les transferts arrivent dans une boîte vide.
- Bot **Lia Conseillère IA** (id 40512) : flux préparé le 05/10/2026 à l'identique de Rachoux v4 (webhook vers lialalionne.com, transfert vers LIA LA LIONNE). Publié (version 2) et activé le 05/10/2026 à 15h44 — module bot avancé Callbell (59 €/mois). Testé en production : catalogue, routines, conversation IA, transfert santé.
- Version 3 publiée le 05/10/2026 à 20h48 : condition « Async » (avant « Humain ») → Attendre la réponse → Aller au nœud Dialogue. Les conversations déjà en cours restent sur l'ancienne version jusqu'à leur fin : « Fermer et redémarrer le robot » pour les basculer.
- Version 4 publiée le 05/10/2026 à 21h00 : nœud « Transcrire le vocal » (action Callbell « Transcrire un fichier audio », source = dernière réponse du contact, résultat dans « Dernier succès du webhook ») entre « en train d'écrire » et « Dialogue » ; les deux « Aller au nœud » pointent désormais vers ce nœud. Corps du webhook : `{"phone":"{{user_phone}}","reponse":"{{last_user_input}}","vocal":"{{last_webhook_success}}","piece_jointe":"{{last_user_attachment_url}}"}`. La transcription est facturée sur le portefeuille de crédits IA Callbell. Côté site, une transcription identique à la dernière réponse envoyée (transcription échouée, variable non vidée) est ignorée, et une URL de fichier n'est jamais prise pour un message.
- Version 5 publiée le 05/10/2026 à 21h11, après l'incident de 21h05 (même réponse renvoyée en boucle) : Callbell n'échappe pas les variables dans le corps JSON ; la transcription rangée dans « Dernier succès du webhook » contenait l'ancienne réponse (sur plusieurs lignes), le JSON devenait invalide, le site répondait 422 et Callbell renvoyait l'ancienne réponse. Correctifs : `vocal` retiré du corps en attendant le déploiement ; le site relit désormais un corps Callbell mal échappé (`repairCallbellBody`) ; nouvelle condition « Réponse IA » (etat contient `ia`) : seule cette branche affiche le texte du webhook, et le cas « Par défaut » (échec du webhook) envoie « Désolée, votre message ne m'est pas bien parvenu 🙏 Pouvez-vous le renvoyer ? » au lieu de l'ancienne réponse.
- `CALLBELL_TEAM_UUID` de l'équipe LIA LA LIONNE : `d24d7d8eef9d4fb4a614ba96de15df9c` (0 membre au 05/10/2026).
- `CALLBELL_API_TOKEN` : même clé que Rachoux (Paramètres › Paramètres de l'API du compte).


```
start (message entrant)
  → Afficher « en train d'écrire… »
  → Webhook « Dialogue »  POST https://lialalionne.com/api/bot/v1/dialogue?bot_token=<BOT_API_KEY>
       corps : {"phone": "{{numéro du contact}}", "reponse": "{{dernier message reçu}}"}
       réponse : text → variable de succès ; etat → variable testée
      → Condition « Humain » (etat contient rx_humain)
          → Envoyer message (text) → Assigner à la boîte de réception de l'équipe Chez Lia   [fin]
      → Par défaut
          → Envoyer message (text) → Attendre la réponse du contact → Aller au nœud : Webhook « Dialogue »   [boucle]
```

Les contraintes Callbell découvertes sur Rachoux s'appliquent (voir `Guide_Callbell_Rachoux.md`) : une boucle doit passer par « Attendre la réponse du contact » ; une conversation bloquée sous une ancienne version se répare avec « Fermer et redémarrer le robot ».

Réponse de l'endpoint : toujours HTTP 200 + `{"ok": true, "etat": "async" | "ia" | "rx_humain", "text": "…"}` (401 si le jeton est faux).

**Mode différé (`etat = async`, depuis le 05/10/2026).** Callbell coupe un webhook après 10 secondes et continue alors le flux avec l'ancienne valeur de la variable : la cliente recevait la réponse précédente une seconde fois. Désormais l'endpoint répond immédiatement `async` (texte vide), puis l'IA travaille après la réponse HTTP (`BotAiReplyJob`, lancé en `afterResponse`, sans worker) et envoie elle-même son message via `POST /v1/messages/send`. Un verrou par numéro traite les messages d'une même cliente l'un après l'autre. En cas de transfert, le message part avec `team_uuid` (`CALLBELL_TEAM_UUID`) et `bot_status = bot_end`. Dans le flux, une condition « Async » (etat contient `async`) placée avant « Humain » va directement au nœud « Attendre la réponse du contact », sans envoyer de message.

**Livraison par le webhook « Résultat » (depuis le 06/10/2026, `BOT_AI_REPLY_CHANNEL=poll`, défaut).** Le 06/10, aucune réponse envoyée par l'API Callbell n'apparaissait dans les conversations : la livraison ne dépend plus de l'API. Le job dépose la réponse dans `BotReplyBox` (cache, 30 min) ; après « Async », le flux appelle `POST /api/bot/v1/resultat?bot_token=…` `{"phone": "{{user_phone}}"}`, qui attend jusqu'à 8 s (`BOT_AI_POLL_SECONDS`) et renvoie `{etat: "ia" | "rx_humain", text}`, `{etat: "async"}` (encore en préparation) ou `{etat: "vide"}`. Le flux enchaîne jusqu'à trois appels « Résultat » (≈ 25 s), puis affiche un message d'attente. `BOT_AI_REPLY_CHANNEL=api` rétablit l'envoi par l'API Callbell (avec repli sur la boîte si l'API refuse).

**Diagnostic** : `GET /api/bot/v1/diagnostic?phone=…&bot_token=…` renvoie l'état de la configuration (sans secret) et les 15 dernières lignes du journal de la conversation (messages, outils, raison de transfert).

**Notes vocales.** Le corps du webhook peut contenir `"vocal": "{{transcription}}"` (action Callbell de transcription audio) : l'IA reçoit alors `[Note vocale] …`. Une variable non remplacée (`{{…}}`) est ignorée.

## 3. La conseillère IA

- **Consignes** : `resources/prompts/bot-chez-lia.md`. Fichier texte modifiable sans toucher au code : ton, règles santé, contre-indications, parcours de commande, cas de transfert. Le catalogue, les routines, la livraison et les moyens de paiement sont ajoutés automatiquement depuis la base à chaque message.
- **Outils** (`app/Services/BotAi/BotAiTools.php`) : `rechercher_produits`, `fiche_produit`, `lister_routines`, `infos_boutique`, `calculer_devis`, `creer_commande`, `payer_mobile_money`, `verifier_paiement`, `lien_paiement_carte`, `suivi_commandes`, `verifier_code_promo`, `transferer_humain`.
- **Garde-fous dans le code**, en plus des consignes :
  - l'IA n'agit que pour le numéro WhatsApp qui écrit (elle ne choisit jamais le téléphone) ;
  - `creer_commande` est refusée si les articles ne sont pas exactement ceux du dernier `calculer_devis` ;
  - prix, stock, livraison et remises viennent toujours de la base ;
  - sans clé Claude, ou si l'API est en erreur, la conversation passe à l'équipe (`rx_humain`).
- **Mémoire** : `bot_conversations` garde les échanges récents (40 messages max) et l'état `bot` / `human`. Une conversation transférée reste à l'équipe jusqu'à 12 h de silence.
- **Journal** : `bot_messages` enregistre chaque message de la cliente, chaque appel d'outil (entrée, résultat, erreur) et chaque réponse. C'est la base de la relecture hebdomadaire des consignes.
- **Coût** : facturé à l'usage par Anthropic sur un compte API séparé (crédits prépayés sur platform.claude.com), indépendant de tout abonnement Claude. Haiku 4.5 : 1 $ / million de jetons en entrée, 5 $ en sortie, 0,10 $ pour la partie lue en cache. Chaque appel envoie ~4 500 jetons fixes (consignes + catalogue + outils, mis en cache) + l'historique ; ordre de grandeur : 0,005 $ par message de cliente, 0,05–0,10 $ par conversation complète avec commande.
- **Écran admin** : *Marketing › Conversations WhatsApp* — liste des conversations (IA / équipe, raison du transfert), journal complet (messages + outils), bouton « Rendre à l'IA ».
- Messages sans texte : les notes vocales transcrites par Callbell sont comprises ; pour une photo, l'IA demande à la cliente d'écrire sa question (pas de lecture d'image) et ne demande jamais de capture d'écran.

## 4. Paiement

- **Mobile Money dans WhatsApp** (comme Rachoux) : après la commande, l'IA demande le numéro à débiter puis appelle `payer_mobile_money`. La cliente reçoit la demande FlexPay sur son téléphone et valide avec son code secret. Le webhook FlexPay confirme la commande et Callbell lui envoie « ✅ Paiement reçu ». `verifier_paiement` permet de revérifier à la demande.
- **Carte** : `lien_paiement_carte` renvoie un lien `/payer/{token}` (récapitulatif + bouton « Payer par carte » → passerelle FlexPay carte). Valable 72 h, inutilisable une fois la commande payée. Depuis le 05/10/2026, l'appel à la passerelle carte reprend le format de Rachoux (champs `callback_url`, `approve_url`… et `authorization` dans le corps) : l'ancien format ne renvoyait aucune URL de paiement, sur le site comme sur WhatsApp. La vérification FlexPay utilise `GET …/check/{orderNumber}`.
- **Devise** : `payer_mobile_money` et `lien_paiement_carte` acceptent `currency` (`CDF` ou `USD`) ; la commande en attente est convertie (lignes, livraison, remise, total, paiement) aux taux du site. La page `/payer/…` propose aussi « Payer plutôt en dollars / en francs ». Chaque demande de paiement porte une référence unique (`LL-<commande>-XXXX`) pour pouvoir relancer.
- **À la livraison** (si activé dans l'admin) : la commande passe directement « En préparation ».

## 5. Messages de suivi (Callbell)

Pour les commandes `source = whatsapp`, à chaque changement de statut (`BotNotifier::messageFor`) : paiement reçu, en préparation (avec le montant à préparer si paiement à la livraison), expédiée / prête au retrait, livrée, annulée. Un message libre n'est délivré que dans les 24 h suivant le dernier message de la cliente. Au-delà, il faudra un modèle Meta (comme le rappel d'abonnement Rachoux).

## 5 bis. Sur le site

*Paramètres › Boutique › WhatsApp* : numéro WhatsApp de la boutique (celui du canal Callbell). Il active le bouton flottant « Une question ? » sur toutes les pages et le bouton vert « Commander sur WhatsApp » sur chaque fiche produit (message pré-rempli avec le nom et le lien du produit).

## 6. API directe (outils, tests, autres intégrations)

Toutes les routes exigent la clé. Les prix sont renvoyés en CDF et USD (`{"cdf", "usd", "label"}`). Le téléphone est accepté sous tous les formats.

| Méthode | URL | Usage |
| --- | --- | --- |
| POST | `/api/bot/v1/dialogue` | Point d'entrée Callbell (IA) |
| GET | `/api/bot/boutique` | Livraison, retrait, paiements, devises |
| GET | `/api/bot/catalogue?q=&categorie=` | Produits actifs |
| GET | `/api/bot/produits/{sku-ou-slug}` | Fiche complète |
| GET | `/api/bot/routines` | Gammes, prix séparé et prix kit |
| GET | `/api/bot/clientes/{telephone}` | Profil, points, dernières commandes |
| POST | `/api/bot/devis` | Récapitulatif sans création |
| POST | `/api/bot/commandes` | Création (après le « oui ») |
| GET | `/api/bot/commandes/{numero}?phone=` | Statut |
| POST | `/api/bot/commandes/{numero}/mobile-money` | Push FlexPay `{phone, payer_phone?, operator?}` |
| POST | `/api/bot/commandes/{numero}/verifier` | Revérifier le paiement `{phone}` |
| POST | `/api/bot/commandes/{numero}/lien-carte` | Lien de paiement par carte `{phone}` |
| POST | `/api/bot/promo/verifier` | Code promo |

Exemple de commande :

```json
{
  "phone": "243812345678",
  "name": "Grace Mbuyi",
  "items": [{"sku": "CL-FES-0002"}, {"sku": "CL-COR-0007", "variant": "Grand format", "quantity": 2}],
  "fulfillment_type": "delivery",
  "address": "12 avenue des Fleurs",
  "commune": "Gombe",
  "payment_method": "mobile_money"
}
```

## 7. Tester

```bash
php artisan test --filter=Bot
curl -X POST "https://lialalionne.com/api/bot/v1/dialogue?bot_token=<jeton>" \
  -H "Content-Type: application/json" -d '{"phone":"+243810000001","reponse":"bonjour"}'
```

Comme pour Rachoux, un `curl` ne suffit pas : toujours valider par un vrai échange de plusieurs messages sur WhatsApp après un changement du flux Callbell.

Recette manuelle (depuis un téléphone qui n'est pas celui de la boutique) :

- [ ] « bjr » → accueil chaleureux
- [ ] « je veux un ventre plat » → questions puis routine Ventre plat avec prix kit
- [ ] Demander un complément → l'IA pose la question grossesse / allaitement / traitement
- [ ] Commander → récapitulatif → « oui » → numéro de commande → push Mobile Money reçu
- [ ] Choisir la carte → lien `/payer/…` fonctionnel
- [ ] « où est ma commande ? » → statut
- [ ] « je veux parler à quelqu'un » → transfert à l'équipe, le bot se tait
- [ ] Question hors sujet → refus poli et retour à la boutique
