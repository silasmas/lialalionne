<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\CurrencyService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

/**
 * Widget dashboard : indicateurs clés de la boutique.
 */
class StatsOverview extends StatsOverviewWidget
{
  protected static ?int $sort = 1;

  /**
   * Retourne les statistiques affichées sur le dashboard.
   *
   * @return array<int, Stat>
   */
  protected function getStats(): array
  {
    $currencyService = app(CurrencyService::class);
    $primaryCurrency = $currencyService->primaryCurrency();

    $revenue = Order::query()
      ->whereIn('status', [
        OrderStatus::Paid,
        OrderStatus::Processing,
        OrderStatus::Shipped,
        OrderStatus::Delivered,
      ])
      ->get()
      ->sum(function (Order $order) use ($currencyService, $primaryCurrency): float {
        if ($order->currency === $primaryCurrency) {
          return (float) $order->total;
        }

        $amountEur = $currencyService->convertToEur((float) $order->total, (string) $order->currency);

        return $currencyService->convertFromEur($amountEur, $primaryCurrency);
      });

    $pendingOrders = Order::query()
      ->where('status', OrderStatus::Pending)
      ->count();

    $lowStock = Product::query()
      ->where('track_stock', true)
      ->where('is_active', true)
      ->where('stock', '<=', 5)
      ->count();

    $customers = User::query()
      ->where('is_admin', false)
      ->count();

    return [
      Stat::make('Chiffre d\'affaires', $currencyService->format($revenue, $primaryCurrency))
        ->description('Commandes payées et livrées')
        ->descriptionIcon('heroicon-m-banknotes')
        ->color('success'),
      Stat::make('Commandes en attente', (string) $pendingOrders)
        ->description('Paiement ou traitement')
        ->descriptionIcon('heroicon-m-clock')
        ->color('warning'),
      Stat::make('Stock bas', (string) $lowStock)
        ->description('Produits ≤ 5 unités')
        ->descriptionIcon('heroicon-m-exclamation-triangle')
        ->color($lowStock > 0 ? 'danger' : 'gray'),
      Stat::make('Clients', (string) $customers)
        ->description('Comptes enregistrés')
        ->descriptionIcon('heroicon-m-users')
        ->color('info'),
    ];
  }
}
