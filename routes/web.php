<?php

use App\Http\Controllers\FlexPayWebhookController;
use App\Http\Controllers\LogoutController;
use App\Http\Controllers\PaymentWebhookController;
use App\Livewire\Account\DashboardPage;
use App\Livewire\Account\FavoritesPage;
use App\Livewire\Account\LoginPage;
use App\Livewire\Account\OrderListPage;
use App\Livewire\Account\OrderShowPage;
use App\Livewire\Account\RegisterPage;
use App\Livewire\Install\SetupPage;
use App\Livewire\Shop\AboutPage;
use App\Livewire\Shop\CartPage;
use App\Livewire\Shop\ComingSoonPage;
use App\Livewire\Shop\ComparePage;
use App\Livewire\Shop\CheckoutCancel;
use App\Livewire\Shop\CheckoutPage;
use App\Livewire\Shop\HomePage;
use App\Livewire\Shop\LegalPage;
use App\Livewire\Shop\OrderConfirmation;
use App\Livewire\Shop\ProductCatalog;
use App\Livewire\Shop\ProductShow;
use Illuminate\Support\Facades\Route;

Route::get('/install', SetupPage::class)->name('install.setup');
Route::get('/coming-soon', ComingSoonPage::class)->name('coming-soon');

Route::get('/', HomePage::class)->name('home');
Route::get('/a-propos', AboutPage::class)->name('shop.about');
Route::get('/boutique', ProductCatalog::class)->name('shop.catalog');
Route::get('/panier', CartPage::class)->name('shop.cart');
Route::get('/comparer', ComparePage::class)->name('shop.compare');
Route::get('/checkout', CheckoutPage::class)->name('shop.checkout');
Route::get('/commande/succes', OrderConfirmation::class)->name('checkout.success');
Route::get('/commande/annulation/{order?}', CheckoutCancel::class)->name('checkout.cancel');
Route::post('/paiement/webhook', PaymentWebhookController::class)->name('payment.webhook');
Route::post('/paiement/webhook/flexpay', FlexPayWebhookController::class)->name('payment.webhook.flexpay');
Route::post('/paiement/webhook/mobile-money', FlexPayWebhookController::class)->name('payment.webhook.mobile-money');
Route::get('/paiement/mobile-money/{order:order_number}', function (\App\Models\Order $order) {
  return redirect()->route('shop.checkout', ['order' => $order->order_number]);
})->name('payment.mobile-money');
Route::get('/produits/{product:slug}', ProductShow::class)->name('products.show');
Route::get('/pages/{page}', LegalPage::class)->name('legal.show');

Route::middleware('guest')->group(function (): void {
  Route::get('/connexion', LoginPage::class)->name('account.login');
  Route::get('/inscription', RegisterPage::class)->name('account.register');
});

Route::middleware('auth')->group(function (): void {
  Route::post('/deconnexion', LogoutController::class)->name('account.logout');
  Route::get('/mon-compte', DashboardPage::class)->name('account.dashboard');
  Route::get('/mon-compte/favoris', FavoritesPage::class)->name('account.favorites');
  Route::get('/mon-compte/commandes', OrderListPage::class)->name('account.orders');
  Route::get('/mon-compte/commandes/{order}', OrderShowPage::class)->name('account.orders.show');
});
