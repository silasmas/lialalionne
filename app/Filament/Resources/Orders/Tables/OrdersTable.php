<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Enums\OrderStatus;
use App\Filament\Resources\Orders\OrderResource;
use App\Services\CurrencyService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des commandes.
 */
class OrdersTable
{
  /**
   * Configure les colonnes et actions de la table.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->defaultSort('created_at', 'desc')
      ->columns([
        TextColumn::make('order_number')
          ->label('N° commande')
          ->searchable()
          ->sortable(),
        TextColumn::make('user.name')
          ->label('Client')
          ->searchable()
          ->placeholder('Invité'),
        TextColumn::make('status')
          ->label('Statut')
          ->badge()
          ->formatStateUsing(fn (OrderStatus $state): string => $state->label())
          ->color(fn (OrderStatus $state): string => match ($state) {
            OrderStatus::Pending => 'gray',
            OrderStatus::Paid => 'info',
            OrderStatus::Processing => 'warning',
            OrderStatus::Shipped => 'primary',
            OrderStatus::Delivered => 'success',
            OrderStatus::Cancelled => 'danger',
          }),
        TextColumn::make('total')
          ->label('Total')
          ->formatStateUsing(fn ($state, $record) => app(CurrencyService::class)->formatOrderAmount((float) $state, $record->currency))
          ->sortable(),
        TextColumn::make('currency')
          ->label('Devise')
          ->toggleable(isToggledHiddenByDefault: true),
        TextColumn::make('tracking_number')
          ->label('Suivi')
          ->toggleable(),
        TextColumn::make('created_at')
          ->label('Date')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->filters([
        SelectFilter::make('status')
          ->label('Statut')
          ->options(collect(OrderStatus::cases())->mapWithKeys(
            fn (OrderStatus $status) => [$status->value => $status->label()]
          )),
      ])
      ->recordActions([
        EditAction::make(),
      ])
      ->recordUrl(fn ($record) => OrderResource::getUrl('edit', ['record' => $record]))
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
