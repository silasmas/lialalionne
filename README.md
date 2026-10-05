# Lialalionne — E-commerce soins corporels

Boutique en ligne professionnelle pour la vente de produits corporels (peau, fessier, visage…).  
Stack : **Laravel 13** · **Filament 5** (admin) · **Livewire 4** (boutique) · **Tailwind CSS**

---

## Légende avancement

| Symbole | Signification |
|---------|---------------|
| ✅ | Terminé |
| 🔄 | En cours |
| ⬜ | À faire |
| ⏸️ | Reporté / phase ultérieure |

---

## Phase 0 — Fondations projet

| # | Tâche | Statut |
|---|-------|--------|
| 0.1 | Initialisation Laravel 13 | ✅ |
| 0.2 | Configuration `.env` (MySQL — base `lialalionne`) | ✅ |
| 0.3 | Installation Filament 5 (panel admin `/admin`) | ✅ |
| 0.4 | Installation Livewire 4 + Tailwind + Vite | ✅ |
| 0.5 | Structure dossiers (`Services`, `Enums`, `Livewire`, `Events`) | ✅ |
| 0.6 | README de suivi (ce fichier) | ✅ |

---

## Phase 1 — Base de données & modèles

### Schéma relationnel

```
Category ──< Product ──< ProductVariant
    │            │
    │            └──< ProductImage
    │
User (client) ──< Order ──< OrderItem ──> Product
    │              │
    │              ├──< OrderAddress
    │              └──< Payment
    │
Cart (session/user) ──< CartItem ──> Product

ShippingZone ──< ShippingRate
```

### Migrations

| # | Table | Statut |
|---|-------|--------|
| 1.1 | `users` (+ phone, is_admin) | ✅ |
| 1.2 | `categories` | ✅ |
| 1.3 | `products` | ✅ |
| 1.4 | `product_variants` | ✅ |
| 1.5 | `product_images` | ✅ |
| 1.6 | `carts` / `cart_items` | ✅ |
| 1.7 | `orders` / `order_items` | ✅ |
| 1.8 | `order_addresses` | ✅ |
| 1.9 | `payments` | ✅ |
| 1.10 | `shipping_zones` / `shipping_rates` | ✅ |
| 1.11 | `coupons` | ✅ |
| 1.12 | `reviews` | ⏸️ Phase 2 |

### Modèles Eloquent

| Modèle | Statut |
|--------|--------|
| `User` (+ FilamentUser, relations) | ✅ |
| `Category` | ✅ |
| `Product` | ✅ |
| `ProductVariant` | ✅ |
| `ProductImage` | ✅ |
| `Cart` / `CartItem` | ✅ |
| `Order` / `OrderItem` | ✅ |
| `OrderAddress` | ✅ |
| `Payment` | ✅ |
| `ShippingZone` / `ShippingRate` | ✅ |

### Enums

| Enum | Statut |
|------|--------|
| `OrderStatus` | ✅ |
| `PaymentStatus` | ✅ |
| `PaymentMethod` | ✅ |

---

## Phase 2 — Admin Filament

| # | Ressource / Fonctionnalité | Statut |
|---|---------------------------|--------|
| 2.1 | Panel admin + authentification | ✅ |
| 2.2 | `CategoryResource` (CRUD) | ✅ |
| 2.3 | `ProductResource` (CRUD + images + variantes) | ✅ |
| 2.4 | `OrderResource` (liste, détail, changement statut) | ✅ |
| 2.5 | `CustomerResource` (`UserResource` — clients) | ✅ |
| 2.6 | `CouponResource` | ✅ |
| 2.7 | `ShippingZoneResource` (+ tarifs) | ✅ |
| 2.8 | Dashboard (CA, commandes récentes, stock bas) | ✅ |
| 2.10 | Page paramètres boutique (`ShopSettings`) | ✅ |
| 2.11 | Rôles & permissions (`filament-shield`) | ⏸️ |

---

## Phase 3 — Boutique Livewire (frontend)

| # | Composant / Page | Statut |
|---|-----------------|--------|
| 3.1 | Layout principal (`layouts/shop`) | ✅ |
| 3.2 | Page d'accueil (`Shop/HomePage`) | ✅ |
| 3.3 | Catalogue + filtres (`Shop/ProductCatalog`) | ✅ |
| 3.4 | Fiche produit (`Shop/ProductShow`) | ✅ |
| 3.5 | Panier (`CartPage`, `CartIcon`, `CartService`) | ✅ |
| 3.6 | Checkout (`CheckoutPage` + livraison) | ✅ |
| 3.7 | Confirmation commande + paiement | ✅ |
| 3.8 | Compte client | ✅ |
| 3.9 | Historique commandes | ✅ |
| 3.10 | Pages légales (CGV, confidentialité, retours) | ✅ |
| 3.11 | Recherche + filtres catalogue | ✅ |
| 3.12 | Panier / favoris depuis cartes produit | ✅ |
| 3.13 | Sélecteur devise CDF / USD | ✅ |
| 3.14 | Retrait en boutique (checkout) | ✅ |
| 3.15 | Auth OTP (email / SMS) + fusion panier invité | ✅ |
| 3.16 | Favoris produits (`/mon-compte/favoris`) | ✅ |

---

## Phase 4 — Services métier

| # | Service | Statut |
|---|---------|--------|
| 4.1 | `CartService` | ✅ |
| 4.2 | `OrderService` | ✅ (base) |
| 4.3 | `PaymentService` | ✅ (FlexPay + Stripe fallback + simulation) |
| 4.4 | `ShippingService` | ✅ |
| 4.5 | `StockService` | ✅ |
| 4.6 | `CurrencyService` | ✅ |
| 4.7 | `FavoriteService` | ✅ |
| 4.8 | `OtpService` + `KeccelSmsService` | ✅ |
| 4.9 | `SiteSettingsService` | ✅ |

| Event | Statut |
|-------|--------|
| `OrderPlaced` | `SendOrderConfirmationEmail` | ✅ |

---

## Phase 5 — Paiement & livraison

| # | Tâche | Statut |
|---|-------|--------|
| 5.1 | Paiement Mobile Money (push + vérification) | ✅ |
| 5.2 | Paiement carte (redirection passerelle) | ✅ |
| 5.3 | Webhooks passerelle (`/paiement/webhook/flexpay`) | ✅ |
| 5.4 | Stripe (fallback carte) + webhook | ✅ |
| 5.5 | Simulation locale (dev sans passerelle) | ✅ |
| 5.6 | Paiement à la livraison | ⏸️ |
| 5.7 | Zones livraison RD Congo | ✅ |
| 5.8 | Numéro de suivi (admin + compte client) | ✅ |
| 5.9 | Email expédition automatique | ✅ |
| 5.10 | Intégration transporteur | ⏸️ |

---

## Phase 6 — Qualité, SEO & légal

| # | Tâche | Statut |
|---|-------|--------|
| 6.1 | Validation stricte (règles Livewire renforcées : regex téléphone, mot de passe complexe) | ✅ |
| 6.2 | Tests PHPUnit (devises, panier, paiement simulé, expédition) | ✅ |
| 6.3 | SEO (meta/OG/Twitter, JSON-LD Product & Organization, sitemap.xml, robots.txt, noindex pages privées) | ✅ |
| 6.4 | RGPD (bandeau cookies fonctionnel + journalisation serveur, export de données, suppression de compte) | ✅ |
| 6.5 | CGV, confidentialité, retours | ✅ |
| 6.6 | Optimisation images (vraies photos produits) | ✅ |

---

## Phase 7 — Croissance (post-MVP)

| # | Fonctionnalité | Statut |
|---|----------------|--------|
| 7.1 | Codes promo | ✅ |
| 7.2 | Avis clients (soumission + modération admin + note moyenne) | ✅ |
| 7.3 | Newsletter (inscription + export CSV admin) | ✅ |
| 7.4 | Programme fidélité (1 pt/€ dépensé, 10 pts = 1 € de réduction) | ✅ |
| 7.5 | Analytics (Meta Pixel, gated par consentement RGPD) | ✅ |
| 7.6 | Laravel Scout (recherche full-text MySQL, driver `database`) | ✅ |
| 7.7 | Paiement à la livraison (COD) | ✅ |
| 7.8 | Intégration transporteur (API externe) | ⏸️ Pas de partenaire identifié — livraison gérée en interne |

---

## Phase 8 — Sécurité (audit du 2026-08-31)

| # | Constat | Statut |
|---|---------|--------|
| 8.1 | **Critique** — webhook FlexPay acceptait tout POST sans vérification (paiement falsifiable) | ✅ Corrigé — re-vérification systématique via l'API FlexPay avant confirmation |
| 8.2 | OTP : pas de verrou après échecs répétés (brute-force du code 6 chiffres) | ✅ Corrigé — verrouillage après 5 tentatives |
| 8.3 | Connexion mot de passe sans rate limiting | ✅ Corrigé — 5 tentatives / 60s par email+IP |
| 8.4 | Aucun rate limiting HTTP sur les actions Livewire (connexion, panier, checkout) | ✅ Corrigé — 120 req/min/IP sur le point d'entrée Livewire |
| 8.5 | Webhooks paiement sans limite de débit | ✅ Corrigé — 60 req/min/IP |
| 8.6 | 14 vulnérabilités dans les dépendances composer (guzzle, psr7, commonmark) | ✅ Corrigé — dépendances mises à jour, 0 vulnérabilité restante |
| 8.7 | 4 vulnérabilités npm (outils de build uniquement) | ✅ Corrigé via `npm audit fix` |
| 8.8 | Upload Filament sans taille max explicite | ✅ Corrigé — 5 Mo max |
| 8.9 | Mot de passe inscription : 8 caractères sans complexité | ✅ Corrigé — lettres + chiffres exigés |
| 8.10 | `SESSION_SECURE_COOKIE` non défini | ✅ Corrigé — à passer à `true` en prod HTTPS |
| 8.11 | Bug latent : page d'accueil plantait (500) si aucun produit vedette actif | ✅ Corrigé (trouvé pendant les tests) |
| 8.12 | `APP_DEBUG=true` doit passer à `false` en production | 🔴 Rappel déploiement — non modifiable localement |
| 8.13 | Emails de production (SMTP réel) | 🔴 Rappel déploiement |

---

## Phase 9 — Bot WhatsApp (conseillère Chez Lia)

| # | Tâche | Statut |
|---|-------|--------|
| 9.1 | API bot `/api/bot/*` protégée par clé (`X-Bot-Key`) : catalogue, fiches, routines, infos boutique | ✅ |
| 9.2 | Clientes reconnues par numéro WhatsApp (tous formats), création de compte à la 1re commande | ✅ |
| 9.3 | Devis sans création + commande WhatsApp (`source = whatsapp`), remise routine complète configurable | ✅ |
| 9.4 | Paiement Mobile Money dans WhatsApp (push FlexPay) + lien carte `/payer/{token}` | ✅ |
| 9.5 | Messages WhatsApp de suivi via Callbell (+ webhook sortant signé optionnel) | ✅ |
| 9.6 | Conseillère IA (Claude) : `POST /api/bot/v1/dialogue`, outils, mémoire, journal, transfert humain — consignes dans `resources/prompts/bot-chez-lia.md` | ✅ |
| 9.7 | Bouton WhatsApp flottant + « Commander sur WhatsApp » sur les fiches produits (numéro dans Paramètres › Boutique) | ✅ |
| 9.8 | Flux Callbell + numéro WhatsApp de la boutique + clé Claude en production | ⬜ |
| 9.9 | Écran admin « Conversations WhatsApp » : journal, raison du transfert, « Rendre à l'IA » | ✅ |

Documentation : `docs/BOT_WHATSAPP.md` · Tests : `php artisan test --filter=Bot`

---

## Structure des dossiers

```
app/
├── Enums/              ✅ OrderStatus, PaymentStatus, PaymentMethod
├── Events/             ✅ OrderPlaced
├── Filament/           ✅ Resources (Categories, Products, Orders, Users, ShippingZones)
│   └── Widgets/        ✅ StatsOverview, LatestOrders, LowStockProducts
├── Livewire/
│   ├── Shop/           ✅ HomePage, ProductCatalog, ProductShow, CartPage, CheckoutPage…
│   └── Account/        ✅ Login, Register, Dashboard, Commandes, Favoris
├── Models/             ✅ 14+ modèles (+ Setting, OtpCode, ProductFavorite)
└── Services/           ✅ Cart, Order, Payment, Shipping, Stock, Currency, OTP, Keccel SMS

database/migrations/    ✅ 18 migrations
resources/views/
├── layouts/shop.blade.php     ✅
└── livewire/shop/             ✅
```

---

## Données de démo (seeders)

```bash
php artisan migrate:fresh --seed
```

| Seeder | Contenu |
|--------|---------|
| `UserSeeder` | 1 admin + 5 clients |
| `CategorySeeder` | 5 catégories soins corporels |
| `ProductSeeder` | **200 produits**, variantes, images placeholder |
| `ShippingSeeder` | Zone RD Congo (Standard + Express) |
| `SettingSeeder` | Auth, paiements, devises, retrait boutique |
| `OrderSeeder` | 6 commandes (tous statuts), adresses, paiements |
| `CartSeeder` | 2 paniers (client + invité) |

| Compte | Email | Mot de passe | Rôle |
|--------|-------|--------------|------|
| Admin | `admin@lialalionne.com` | `password` | Panel `/admin` |
| Client | `marie.dupont@email.com` | `password` | Boutique |

---

## Démarrage local

```bash
# Installer les dépendances
composer install
npm install

# Configurer l'environnement
cp .env.example .env
php artisan key:generate

# Créer la base MySQL (Laragon)
# CREATE DATABASE lialalionne CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Paiement (FlexPay RDC — optionnel, simulation si vide)
# FLEXPAY_MARCHAND= / FLEXPAY_API_TOKEN=

# SMS OTP Keccel (optionnel — log local si vide)
# KECCEL_SMS_TOKEN= / KECCEL_SMS_FROM=LIALALIONNE

# Email : MAIL_MAILER=log en dev, smtp en production

# Migrations + données de démo
php artisan migrate:fresh --seed

# Comptes de démo (mot de passe : password)
# Admin  : admin@lialalionne.com
# Client : marie.dupont@email.com

# Lien storage (images produits)
php artisan storage:link

# Assets frontend
npm run build

# Lancer le serveur
php artisan serve
```

| URL | Description |
|-----|-------------|
| http://localhost:8000 | Accueil |
| http://localhost:8000/boutique | Catalogue produits |
| http://localhost:8000/produits/{slug} | Fiche produit |
| http://localhost:8000/panier | Panier |
| http://localhost:8000/checkout | Checkout + paiement |
| http://localhost:8000/commande/succes | Confirmation commande |
| http://localhost:8000/connexion | Connexion client |
| http://localhost:8000/mon-compte | Espace client |
| http://localhost:8000/admin | Panel admin Filament |
| http://localhost:8000/admin/shop-settings | Paramètres boutique |

### Créer un utilisateur admin

```bash
php artisan make:filament-user
# Puis en MySQL :
# UPDATE users SET is_admin = 1 WHERE email = 'votre@email.com';
```

---

## Journal des mises à jour

| Date | Action |
|------|--------|
| 2026-06-19 | Création README + plan projet |
| 2026-06-19 | Init Laravel 13, Filament 5, Livewire 4 |
| 2026-06-19 | Migrations, modèles, enums, services (base) |
| 2026-06-19 | Layout boutique + page d'accueil squelette |
| 2026-06-19 | Migration MySQL (base `lialalionne`) + ressources Filament admin |
| 2026-06-19 | Dashboard widgets + relation managers (variantes, images, tarifs) |
| 2026-06-19 | Catalogue Livewire (/boutique) + fiche produit (/produits/{slug}) |
| 2026-06-19 | Seeders complets (users, catalogue, commandes, paniers, livraison) |
| 2026-06-19 | Panier Livewire (/panier) + icône header + ajout depuis fiche produit |
| 2026-06-19 | Checkout + paiement Stripe (initiate/webhook) + confirmation commande |
| 2026-06-20 | FlexPay Mobile Money + carte, devises CDF/USD, retrait boutique |
| 2026-06-20 | Auth OTP, favoris, paramètres admin, 200 produits, zone livraison RDC |
| 2026-06-24 | Keccel SMS OTP, libellés paiement, admin FC/USD, tests PHPUnit |

---

## Prochaines étapes (priorité)

| Priorité | Tâche | Action |
|----------|-------|--------|
| 🔴 | Paiement production | Déployer sur HTTPS (`SESSION_SECURE_COOKIE=true`, `APP_DEBUG=false`), configurer webhook public, tester Mobile Money + carte |
| 🔴 | Emails | SMTP Hostinger en prod ; `MAIL_MAILER=log` en local |
| 🟠 | SMS OTP | Renseigner `KECCEL_SMS_TOKEN` dans `.env` si mode SMS activé |
| 🟠 | Meta Pixel | Renseigner `META_PIXEL_ID` dans `.env` si utilisé |
| 🟡 | Email expédition | Notifier le client quand `tracking_number` est renseigné |
| 🟡 | Tests | Étendre la couverture PHPUnit (checkout Livewire, OTP) |
| ⏸️ | Post-MVP | Programme fidélité paliers VIP, intégration transporteur (si partenaire identifié) |

### Commandes utiles

```bash
# Lancer les tests
php artisan test

# Appliquer la zone livraison RDC (si base déjà existante)
php artisan migrate
```

---

## Décision GraphQL

**Non retenu pour le MVP.** Livewire + Eloquent couvrent les besoins actuels.  
Réévaluer uniquement si une app mobile native ou un frontend SPA séparé est prévu.
