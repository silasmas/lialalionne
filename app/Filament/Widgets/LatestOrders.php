<?php

namespace App\Filament\Widgets;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Models\Order;
use App\Services\CurrencyService;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

/**
 * Widget dashboard : dernières commandes reçues.
 */
class LatestOrders extends TableWidget
{
  protected static ?int $sort = 2;

  protected int|string|array $columnSpan = 'full';

  protected static ?string $heading = 'Dernières commandes';

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->query(fn (): Builder => Order::query()->latest()->limit(10))
      ->columns([
        TextColumn::make('order_number')
          ->label('N° commande'),
        TextColumn::make('user.name')
          ->label('Client')
          ->placeholder('Invité'),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (OrderStatus $state): string => $state->label()),
        TextColumn::make('total')
          ->label('Total')
          ->formatStateUsing(fn ($state, Order $record) => app(CurrencyService::class)->formatOrderAmount((float) $state, $record->currency)),
        TextColumn::make('created_at')
          ->label('Date')
          ->dateTime('d/m/Y H:i'),
      ])
      ->recordActions([
        ViewAction::make()
          ->url(fn (Order $record): string => OrderResource::getUrl('edit', ['record' => $record])),
      ])
      ->paginated(false);
  }
}
