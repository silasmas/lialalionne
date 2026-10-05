<?php

use App\Http\Controllers\Bot\BotCatalogController;
use App\Http\Controllers\Bot\BotDialogueController;
use App\Http\Controllers\Bot\BotOrderController;
use App\Http\Middleware\AuthenticateBot;
use Illuminate\Support\Facades\Route;

/*
| API du bot WhatsApp (conseillère Chez Lia).
| Toutes les routes exigent l'en-tête X-Bot-Key = BOT_API_KEY.
| Documentation : docs/BOT_WHATSAPP.md
*/
Route::prefix('bot')
  ->middleware([AuthenticateBot::class, 'throttle:120,1'])
  ->name('bot.api.')
  ->group(function (): void {
    Route::get('/boutique', [BotCatalogController::class, 'shop'])->name('shop');
    Route::get('/catalogue', [BotCatalogController::class, 'index'])->name('catalogue');
    Route::get('/produits/{reference}', [BotCatalogController::class, 'show'])->name('products.show');
    Route::get('/routines', [BotCatalogController::class, 'routines'])->name('routines');

    Route::get('/clientes/{phone}', [BotOrderController::class, 'customer'])->name('customers.show');
    Route::post('/devis', [BotOrderController::class, 'quote'])->name('quote');
    Route::post('/commandes', [BotOrderController::class, 'store'])->name('orders.store');
    Route::get('/commandes/{orderNumber}', [BotOrderController::class, 'show'])->name('orders.show');
    Route::post('/commandes/{orderNumber}/mobile-money', [BotOrderController::class, 'payMobileMoney'])->name('orders.mobile-money');
    Route::post('/commandes/{orderNumber}/verifier', [BotOrderController::class, 'verifyPayment'])->name('orders.verify');
    Route::post('/commandes/{orderNumber}/lien-carte', [BotOrderController::class, 'cardLink'])->name('orders.card-link');
    Route::post('/promo/verifier', [BotOrderController::class, 'coupon'])->name('coupon');
  });

// Point d'entrée unique de la conseillère IA (webhook Callbell).
Route::post('/bot/v1/dialogue', BotDialogueController::class)
  ->middleware([AuthenticateBot::class, 'throttle:300,1'])
  ->name('bot.api.dialogue');
