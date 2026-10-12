# Bot WhatsApp — Chez Lia / Lialalionne

La conseillère Chez Lia répond aux clientes sur WhatsApp. Depuis le 12/10/2026, Callbell est retiré : le numéro de la boutique (+243 965 822 818) passe par l'API WhatsApp Cloud de Meta, et le relais SDev apporte chaque message au site. Toute l'intelligence vit dans Laravel (Claude + outils branchés sur la boutique).

```
Cliente WhatsApp → relais SDev → POST /api/bot/v1/dialogue → BotAiAgent (Claude + outils) → {ok, etat, text} → relais → Cliente
                                                                     ↓
                                     catalogue, devis, commande, push Mobile Money, suivi (BotCommerceService)
Site (FlexPay confirme, admin expédie…) → BotNotifier → WhatsApp Cloud (Meta) → message de suivi à la cliente
```

Le contrat du dialogue ne change pas : corps `{phone, reponse}` (et, en option, `vocal`, `piece_jointe`), réponse `{ok, etat, text}`, clé `BOT_API_KEY` dans l'en-tête `X-Bot-Key`. Le relais attend jusqu'à 30 secondes : la réponse de l'IA part dans ce même appel (`etat` = `ia` ou `rx_humain`).

## 1. Configuration (.env)

| Variable | Rôle |
| --- | --- |
| `BOT_API_KEY` | Clé secrète. Le relais l'envoie dans l'en-tête `X-Bot-Key` (le paramètre `?bot_token=` reste accepté). Vide = API désactivée (503). |
| `ANTHROPIC_API_KEY` | Clé de l'API Claude (console.anthropic.com). Sans clé, chaque message est transféré à l'équipe. |
| `BOT_AI_MODEL` | Modèle Claude. Défaut `claude-haiku-4-5-20251001`. |
| `BOT_AI_MAX_TOKENS` | Longueur maximale d'une réponse (défaut 1024). |
| `BOT_AI_SESSION_TTL_HOURS` | Après cette pause, la conversation repart de zéro (défaut 12 h). Le journal est conservé. |
| `WHATSAPP_ACCESS_TOKEN` | Jeton de l'API WhatsApp Cloud (Meta). Messages de suivi de commande. |
| `WHATSAPP_PHONE_NUMBER_ID` | Identifiant du numéro WhatsApp Cloud (+243 965 822 818). |
| `BOT_KIT_DISCOUNT_PERCENT` | Remise quand une routine complète est commandée. 0 = désactivée. |
| `BOT_PAYMENT_LINK_TTL_HOURS` | Validité du lien de paiement par carte (défaut 72). |
| `BOT_NOTIFY_URL`, `BOT_NOTIFY_SECRET` | Optionnel : webhook sortant signé à chaque changement de statut. |

Générer `BOT_API_KEY` :

```bash
php -r "echo bin2hex(random_bytes(24)), PHP_EOL;"
```

Mise en route serveur :

```bash
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
```

## 2. Relais SDev

```
message entrant
  → POST https://lialalionne.com/api/bot/v1/dialogue
       en-tête : X-Bot-Key: <BOT_API_KEY>
       corps : {"phone": "…", "reponse": "…"}
       réponse (dans le même appel, le relais attend jusqu'à 30 s) :
         {"ok": true, "etat": "ia" | "rx_humain", "text": "…"}
      → etat = rx_humain : afficher text (vide si l'équipe a déjà la main),
        puis laisser la conversation à l'équipe
      → etat = ia : afficher text, attendre le prochain message
```

- `etat` = `ia` : la conseillère répond, `text` est le message à envoyer.
- `etat` = `rx_humain` : transfert à l'équipe. Le premier message de passation est dans `text`. Ensuite, tant que la conversation est à l'équipe, la réponse est `{etat: "rx_humain", text: ""}` — ne rien renvoyer à la cliente.
- `repairCallbellBody` reste en place : un corps JSON mal échappé (guillemets, retours à la ligne) est relu champ par champ.
- `POST /api/bot/v1/resultat` reste fonctionnel pour compatibilité. Le dialogue ne dépose plus de réponse différée : sans dépôt préalable, l'endpoint renvoie `{etat: "vide", text: ""}`.
- **Diagnostic** : `GET /api/bot/v1/diagnostic?phone=…` (clé dans `X-Bot-Key`) renvoie l'état de la configuration (sans secret) et les 15 dernières lignes du journal.

**Notes vocales.** Le corps peut contenir `"vocal"` (transcription) : l'IA reçoit alors `[Note vocale] …`. Une variable non remplacée (`{{…}}`) est ignorée. Une URL de fichier identique à `piece_jointe` n'est pas prise pour un message.

## 3. La conseillère IA

- **Consignes** : `resources/prompts/bot-chez-lia.md`. Fichier texte modifiable sans toucher au code. Le catalogue, les routines, la livraison et les moyens de paiement sont ajoutés automatiquement depuis la base à chaque message. La boutique se présente « Chez Lia — La Lionne » (« Bienvenue chez Lia », jamais « chez Chez Lia »). Les messages en lingala ou en mélange français/lingala reçoivent une réponse dans la même langue, sans commentaire sur la langue.
- **Outils** (`app/Services/BotAi/BotAiTools.php`) : `rechercher_produits`, `fiche_produit`, `lister_routines`, `infos_boutique`, `calculer_devis`, `creer_commande`, `payer_mobile_money`, `verifier_paiement`, `lien_paiement_carte`, `suivi_commandes`, `verifier_code_promo`, `transferer_humain`.
- **Garde-fous dans le code**, en plus des consignes :
  - l'IA n'agit que pour le numéro WhatsApp qui écrit ;
  - `creer_commande` est refusée si les articles ne sont pas exactement ceux du dernier `calculer_devis` ;
  - prix, stock, livraison et remises viennent toujours de la base ;
  - sans clé Claude, ou si l'API est en erreur, la conversation passe à l'équipe (`rx_humain`).
- **Mémoire** : `bot_conversations` garde les échanges récents (40 messages max) et l'état `bot` / `human`. Une conversation transférée reste à l'équipe jusqu'à 12 h de silence.
- **Journal** : `bot_messages` enregistre chaque message de la cliente, chaque appel d'outil et chaque réponse.
- **Écran admin** : *Marketing › Conversations WhatsApp* — liste des conversations (IA / équipe, raison du transfert), journal complet, bouton « Rendre à l'IA ».

## 4. Paiement

- **Mobile Money dans WhatsApp** : après la commande, l'IA demande le numéro à débiter puis appelle `payer_mobile_money`. La cliente valide la demande FlexPay sur son téléphone. Le webhook FlexPay confirme la commande et `BotNotifier` envoie « ✅ Paiement reçu » via l'API Cloud. `verifier_paiement` permet de revérifier à la demande.
- **Carte** : `lien_paiement_carte` renvoie un lien `/payer/{token}` (valable 72 h).
- **Devise** : `payer_mobile_money` et `lien_paiement_carte` acceptent `currency` (`CDF` ou `USD`).
- **À la livraison** (si activé dans l'admin) : la commande passe directement « En préparation ».

## 5. Messages de suivi (WhatsApp Cloud)

Pour les commandes `source = whatsapp`, à chaque changement de statut (`BotNotifier::messageFor`) : paiement reçu, en préparation (avec le montant à préparer si paiement à la livraison), expédiée / prête au retrait, livrée, annulée.

Envoi : `POST https://graph.facebook.com/v21.0/{WHATSAPP_PHONE_NUMBER_ID}/messages`, jeton `WHATSAPP_ACCESS_TOKEN`, destinataire en chiffres avec indicatif (`243…`), timeout 15 s. Un échec est journalisé et n'interrompt ni la commande ni le paiement.

Un message libre n'est délivré que dans les 24 h suivant le dernier message de la cliente. Au-delà, Meta répond le code **131047** : il est noté dans le journal. `WhatsAppCloud::sendTemplate()` est prête pour un modèle approuvé, et n'est pas encore branchée.

## 5 bis. Sur le site

*Paramètres › Boutique › WhatsApp* : numéro WhatsApp de la boutique. Il active le bouton flottant « Une question ? » sur toutes les pages et le bouton vert « Commander sur WhatsApp » sur chaque fiche produit.

## 6. API directe (outils, tests, autres intégrations)

Toutes les routes exigent la clé. Les prix sont renvoyés en CDF et USD (`{"cdf", "usd", "label"}`). Le téléphone est accepté sous tous les formats.

| Méthode | URL | Usage |
| --- | --- | --- |
| POST | `/api/bot/v1/dialogue` | Point d'entrée du relais (IA, réponse synchrone) |
| POST | `/api/bot/v1/resultat` | Ancienne boîte de réponses (compatibilité) |
| GET | `/api/bot/v1/diagnostic` | Configuration (sans secret) et journal |
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

## 7. Tester

```bash
php artisan test --filter=Bot
curl -X POST "https://lialalionne.com/api/bot/v1/dialogue" \
  -H "Content-Type: application/json" \
  -H "X-Bot-Key: <jeton>" \
  -d '{"phone":"+243810000001","reponse":"bonjour"}'
```

Toujours valider par un vrai échange de plusieurs messages sur WhatsApp après un changement.

Recette manuelle (depuis un téléphone qui n'est pas celui de la boutique) :

- [ ] « bjr » → « Bienvenue chez Lia » (jamais « chez Chez Lia »)
- [ ] Un message en lingala ou en mélange → réponse dans la même langue, sans commentaire sur la langue
- [ ] « je veux un ventre plat » → questions puis routine Ventre plat avec prix
- [ ] Demander un complément → l'IA pose la question grossesse / allaitement / traitement
- [ ] Commander → récapitulatif → « oui » → numéro de commande → push Mobile Money reçu
- [ ] Paiement confirmé → message « Paiement reçu » sur WhatsApp (fenêtre de 24 h)
- [ ] « où est ma commande ? » → statut
- [ ] « je veux parler à quelqu'un » → `etat` = `rx_humain`, puis les messages suivants renvoient `text` vide
- [ ] Question hors sujet → refus poli et retour à la boutique
