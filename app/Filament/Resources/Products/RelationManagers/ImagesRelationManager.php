<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Models\Product;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Gestion des images produit dans l'admin Filament (1 principale + 5 illustrations max).
 */
class ImagesRelationManager extends RelationManager
{
  protected static string $relationship = 'images';

  protected static ?string $title = 'Images (1 principale + 5 illustrations max)';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        FileUpload::make('path')
          ->label('Image')
          ->image()
          ->required()
          ->directory('products')
          ->visibility('public'),
        TextInput::make('alt_text')
          ->label('Texte alternatif')
          ->maxLength(255),
        TextInput::make('sort_order')
          ->label('Ordre')
          ->numeric()
          ->default(0)
          ->minValue(0),
        Toggle::make('is_primary')
          ->label('Image principale')
          ->default(false),
      ]);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->defaultSort('sort_order')
      ->columns([
        ImageColumn::make('path')
          ->label('Aperçu'),
        TextColumn::make('alt_text')
          ->label('Alt'),
        TextColumn::make('sort_order')
          ->label('Ordre')
          ->numeric(),
        IconColumn::make('is_primary')
          ->label('Principale')
          ->boolean(),
      ])
      ->headerActions([
        CreateAction::make()
          ->before(function (): void {
            if ($this->getOwnerRecord()->images()->count() >= Product::MAX_IMAGES) {
              Notification::make()
                ->danger()
                ->title('Limite atteinte')
                ->body('Maximum ' . Product::MAX_IMAGES . ' images : 1 principale et ' . Product::MAX_ILLUSTRATION_IMAGES . ' illustrations.')
                ->send();

              throw new Halt();
            }
          })
          ->after(function ($record): void {
            if ($record->is_primary) {
              $record->product->images()
                ->where('id', '!=', $record->id)
                ->update(['is_primary' => false]);
            }
          }),
      ])
      ->recordActions([
        EditAction::make()
          ->after(function ($record): void {
            if ($record->is_primary) {
              $record->product->images()
                ->where('id', '!=', $record->id)
                ->update(['is_primary' => false]);
            }
          }),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
