<?php

namespace App\Filament\Resources\Coupons\Tables;

use App\Enums\CouponType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des codes promo.
 */
class CouponsTable
{
  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->defaultSort('created_at', 'desc')
      ->columns([
        TextColumn::make('code')
          ->label('Code')
          ->searchable()
          ->sortable()
          ->copyable(),
        TextColumn::make('name')
          ->label('Nom')
          ->searchable()
          ->sortable(),
        TextColumn::make('type')
          ->label('Type')
          ->badge()
          ->formatStateUsing(fn (CouponType $state): string => $state->label()),
        TextColumn::make('value')
          ->label('Valeur')
          ->formatStateUsing(function ($state, $record): string {
            return $record->type === CouponType::Percent
              ? rtrim(rtrim(number_format((float) $state, 2, ',', ' '), '0'), ',') . ' %'
              : number_format((float) $state, 2, ',', ' ') . ' €';
          }),
        TextColumn::make('times_used')
          ->label('Utilisations')
          ->formatStateUsing(function ($state, $record): string {
            $max = $record->max_uses;

            return $max !== null ? "{$state} / {$max}" : (string) $state;
          })
          ->sortable(),
        IconColumn::make('is_active')
          ->label('Actif')
          ->boolean(),
        TextColumn::make('ends_at')
          ->label('Expire le')
          ->dateTime('d/m/Y H:i')
          ->placeholder('—')
          ->sortable(),
      ])
      ->recordActions([
        EditAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
