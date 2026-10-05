<?php

namespace App\Filament\Resources\NewsletterSubscribers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des inscrits newsletter.
 */
class NewsletterSubscribersTable
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
      ->defaultSort('subscribed_at', 'desc')
      ->columns([
        TextColumn::make('email')
          ->label('E-mail')
          ->searchable()
          ->sortable(),
        IconColumn::make('unsubscribed_at')
          ->label('Actif')
          ->boolean()
          ->getStateUsing(fn ($record) => $record->unsubscribed_at === null),
        TextColumn::make('user.name')
          ->label('Client associé')
          ->placeholder('—'),
        TextColumn::make('subscribed_at')
          ->label('Inscrit le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->recordActions([
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
