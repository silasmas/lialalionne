<?php

namespace App\Filament\Resources\Reviews\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

/**
 * Configuration de la table de modération des avis clients.
 */
class ReviewsTable
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
        TextColumn::make('product.name')
          ->label('Produit')
          ->searchable()
          ->limit(30),
        TextColumn::make('user.name')
          ->label('Client')
          ->searchable(),
        TextColumn::make('rating')
          ->label('Note')
          ->formatStateUsing(fn (int $state) => str_repeat('★', $state) . str_repeat('☆', 5 - $state)),
        TextColumn::make('title')
          ->label('Titre')
          ->limit(25)
          ->placeholder('—'),
        TextColumn::make('comment')
          ->label('Commentaire')
          ->limit(50)
          ->wrap(),
        IconColumn::make('is_verified_purchase')
          ->label('Achat vérifié')
          ->boolean(),
        IconColumn::make('is_approved')
          ->label('Approuvé')
          ->boolean(),
        TextColumn::make('created_at')
          ->label('Publié le')
          ->dateTime('d/m/Y H:i')
          ->sortable(),
      ])
      ->filters([
        TernaryFilter::make('is_approved')
          ->label('Statut de modération')
          ->trueLabel('Approuvés')
          ->falseLabel('En attente'),
      ])
      ->recordActions([
        Action::make('approve')
          ->label('Approuver')
          ->icon('heroicon-o-check')
          ->color('success')
          ->visible(fn ($record) => !$record->is_approved)
          ->action(fn ($record) => $record->update(['is_approved' => true])),
        Action::make('reject')
          ->label('Rejeter')
          ->icon('heroicon-o-x-mark')
          ->color('warning')
          ->visible(fn ($record) => $record->is_approved)
          ->action(fn ($record) => $record->update(['is_approved' => false])),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
