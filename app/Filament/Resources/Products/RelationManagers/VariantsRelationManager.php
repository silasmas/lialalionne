<?php

namespace App\Filament\Resources\Products\RelationManagers;

use App\Support\SkuGenerator;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

/**
 * Gestion des variantes produit dans l'admin Filament.
 */
class VariantsRelationManager extends RelationManager
{
  protected static string $relationship = 'variants';

  protected static ?string $title = 'Variantes';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public function form(Schema $schema): Schema
  {
    return $schema
      ->components([
        TextInput::make('name')
          ->label('Nom')
          ->required()
          ->maxLength(255)
          ->live(onBlur: true)
          ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
            if ($get('sku_auto')) {
              $set('sku', SkuGenerator::forVariant(
                $state,
                $this->getOwnerRecord()->sku
              ));
            }
          }),
        Toggle::make('sku_auto')
          ->label('Générer le SKU automatiquement')
          ->default(fn ($livewire): bool => ! method_exists($livewire, 'getRecord') || $livewire->getRecord() === null)
          ->dehydrated(false)
          ->live()
          ->afterStateUpdated(function (Set $set, Get $get, bool $state): void {
            if ($state) {
              $set('sku', SkuGenerator::forVariant(
                $get('name'),
                $this->getOwnerRecord()->sku
              ));
            }
          })
          ->helperText('Activez pour générer un code unique, ou désactivez pour le saisir à la main.'),
        TextInput::make('sku')
          ->label('SKU')
          ->required()
          ->maxLength(255)
          ->disabled(fn (Get $get): bool => (bool) $get('sku_auto'))
          ->dehydrated()
          ->default(fn (): string => SkuGenerator::forVariant(
            null,
            $this->getOwnerRecord()->sku
          ))
          ->helperText('Unique parmi produits et variantes.'),
        TextInput::make('price')
          ->label('Prix')
          ->required()
          ->numeric()
          ->prefix('€')
          ->minValue(0),
        TextInput::make('stock')
          ->label('Stock')
          ->required()
          ->numeric()
          ->default(0)
          ->minValue(0),
        Toggle::make('is_active')
          ->label('Active')
          ->default(true),
      ]);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public function table(Table $table): Table
  {
    return $table
      ->recordTitleAttribute('name')
      ->columns([
        TextColumn::make('name')
          ->label('Nom'),
        TextColumn::make('sku')
          ->label('SKU'),
        TextColumn::make('price')
          ->label('Prix')
          ->money('EUR'),
        TextColumn::make('stock')
          ->label('Stock')
          ->numeric(),
        IconColumn::make('is_active')
          ->label('Active')
          ->boolean(),
      ])
      ->headerActions([
        CreateAction::make(),
      ])
      ->recordActions([
        EditAction::make(),
        DeleteAction::make(),
      ])
      ->toolbarActions([
        BulkActionGroup::make([
          DeleteBulkAction::make(),
        ]),
      ]);
  }
}
