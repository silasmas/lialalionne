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
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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

    View::composer([
      'layouts.shopwise',
      'layouts.partials.shopwise.*',
      'livewire.shop.*',
      'livewire.account.*',
      'components.shopwise-*',
    ], ShopwiseComposer::class);
  }
}
