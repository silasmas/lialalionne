<?php

namespace App\Providers;

use App\Events\OrderPlaced;
use App\Events\OrderShipped;
use App\Listeners\SendOrderConfirmationEmail;
use App\Listeners\SendOrderShipmentEmail;
use App\Models\Order;
use App\Observers\OrderObserver;
use App\View\Composers\ShopwiseComposer;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Livewire\Mechanisms\HandleRequests\EndpointResolver;
use Livewire\Mechanisms\HandleRequests\RequireLivewireHeaders;

class AppServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    //
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    Event::listen(OrderPlaced::class, SendOrderConfirmationEmail::class);
    Event::listen(OrderShipped::class, SendOrderShipmentEmail::class);

    Order::observe(OrderObserver::class);

    // Toutes les actions Livewire (connexion, OTP, panier, checkout...) passent
    // par ce point d'entrée unique : on y ajoute un rate limit par IP en
    // défense en profondeur, en plus des limites métier déjà appliquées
    // (ex. OtpService::assertRateLimit) qui restent la protection principale.
    Livewire::setUpdateRoute(function ($handle) {
      return Route::post(EndpointResolver::updatePath(), $handle)
        ->middleware(['web', RequireLivewireHeaders::class, 'throttle:120,1'])
        ->name('livewire.update');
    });

    View::composer([
      'layouts.shopwise',
      'layouts.partials.shopwise.*',
      'livewire.shop.*',
      'livewire.account.*',
      'components.shopwise-*',
    ], ShopwiseComposer::class);
  }
}
