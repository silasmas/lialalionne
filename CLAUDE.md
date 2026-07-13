# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Overview

Lialalionne — e-commerce store for body-care products (skin, face, etc.), targeting the DRC market (Mobile Money payments, CDF/USD currency switching). Stack: **Laravel 13**, **Filament 5** (admin panel at `/admin`), **Livewire 4** (storefront, full-page components), **Tailwind CSS 4** (Vite plugin, no separate config file). See `README.md` for the full phase-by-phase build log, demo accounts, and current TODOs — it is kept up to date and is the best source of "what's done vs pending."

## Commands

Install dependencies and set up the app:
```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed   # seeds demo users, 200 products, orders, carts, shipping zones
php artisan storage:link
npm run build
```

Or use the composer convenience script: `composer run setup`.

Dev server (runs Laravel server + queue listener + log tailing + Vite concurrently):
```bash
composer run dev
```
Or individually: `php artisan serve`, `npm run dev`.

Run tests:
```bash
php artisan test                          # full suite (also runs via `composer test`, which clears config first)
php artisan test --filter=TestName        # single test
```

Create an admin user:
```bash
php artisan make:filament-user
# then in MySQL: UPDATE users SET is_admin = 1 WHERE email = 'you@example.com';
```

Demo accounts (password: `password`): `admin@lialalionne.com` (admin panel), `marie.dupont@email.com` (storefront).

## Architecture

- **Storefront (`/`, `/boutique`, `/panier`, `/checkout`, `/mon-compte/*`)**: full-page Livewire components under `app/Livewire/Shop` and `app/Livewire/Account` (e.g. `HomePage`, `ProductCatalog`, `ProductShow`, `CartPage`, `CheckoutPage`, `DashboardPage`, `OrderListPage`). Routed directly in `routes/web.php` (`Route::get('/boutique', ProductCatalog::class)`), no traditional controllers for shop pages.
- **Admin panel (`/admin`)**: Filament 5 resources under `app/Filament/Resources` — `Categories`, `Products` (with variants/images relation managers), `Orders`, `Users`, `ShippingZones` — plus `app/Filament/Widgets` (stats, latest orders, low-stock) and a custom `ShopSettings` page for store config.
- **Domain model**: `Category → Product → ProductVariant/ProductImage`; `User → Order → OrderItem → Product`, `Order → OrderAddress/Payment`; `Cart → CartItem` (session or user-bound); `ShippingZone → ShippingRate`. Status enums live in `app/Enums` (`OrderStatus`, `PaymentStatus`, `PaymentMethod`).
- **Services layer** (`app/Services`): business logic is pulled out of controllers/components — `CartService`, `OrderService`, `PaymentService` (FlexPay Mobile Money + Stripe fallback + local simulation when no gateway configured), `ShippingService`, `StockService`, `CurrencyService` (CDF/USD switcher), `FavoriteService`, `OtpService` + `KeccelSmsService` (SMS OTP auth), `SiteSettingsService`.
- **Payments**: webhooks at `POST /paiement/webhook` (Stripe) and `POST /paiement/webhook/flexpay` (FlexPay Mobile Money/card). Payment method configured per-order; falls back to local simulation in dev when gateway env vars are empty.
- **Auth**: custom OTP-based auth (email or SMS via Keccel) rather than Breeze/Jetstream, with guest-cart merge on login.
- **Events**: `OrderPlaced` → `SendOrderConfirmationEmail` listener (`app/Events`, `app/Listeners`).
- **i18n/locale**: French-first UI and routes (`/panier`, `/commande/succes`, etc.).

Known gaps per README: coupons, product reviews, newsletter, SEO/sitemap, and GDPR consent are deferred to a post-MVP phase.
