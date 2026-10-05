<?php

namespace App\Filament\Resources\HomeSlides\Tables;

use App\Enums\SlideMediaType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Configuration de la table listing des slides d'accueil.
 */
class HomeSlidesTable
{
  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function configure(Table $table): Table
  {
    return $table
      ->defaultSort('sort_order')
      ->reorderable('sort_order')
      ->columns([
        ImageColumn::make('preview')
          ->label('Aperçu')
          ->getStateUsing(function ($record): ?string {
            if ($record->isImage()) {
              return $record->mediaUrl();
            }

            return $record->posterUrl();
          }),
        TextColumn::make('title')
          ->label('Titre')
          ->searchable()
          ->sortable(),
        TextColumn::make('media_type')
          ->label('Type')
          ->badge()
          ->formatStateUsing(fn (SlideMediaType $state): string => $state->label())
          ->color(fn (SlideMediaType $state): string => $state === SlideMediaType::Video ? 'warning' : 'gray'),
        TextColumn::make('sort_order')
          ->label('Ordre')
          ->numeric()
          ->sortable(),
        IconColumn::make('is_active')
          ->label('Visible')
          ->boolean(),
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
