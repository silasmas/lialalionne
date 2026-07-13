<?php

namespace App\Filament\Resources\Products\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Schéma du formulaire produit admin.
 */
class ProductForm
{
  /**
   * Configure les champs du formulaire produit.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Général')
          ->schema([
            Select::make('category_id')
              ->label('Catégorie')
              ->relationship('category', 'name')
              ->searchable()
              ->preload()
              ->required(),
            TextInput::make('name')
              ->label('Nom')
              ->required()
              ->maxLength(255)
              ->live(onBlur: true)
              ->afterStateUpdated(function ($set, ?string $state): void {
                $set('slug', Str::slug($state ?? ''));
              }),
            TextInput::make('slug')
              ->label('Slug')
              ->required()
              ->maxLength(255)
              ->unique(ignoreRecord: true),
            TextInput::make('sku')
              ->label('SKU')
              ->required()
              ->maxLength(255)
              ->unique(ignoreRecord: true),
            Textarea::make('short_description')
              ->label('Description courte')
              ->rows(2)
              ->columnSpanFull(),
          ])
          ->columns(2),
        Section::make('Contenu')
          ->schema([
            Textarea::make('description')
              ->label('Description')
              ->rows(4)
              ->columnSpanFull(),
            Textarea::make('ingredients')
              ->label('Ingrédients (INCI)')
              ->rows(4)
              ->columnSpanFull(),
            Textarea::make('usage_tips')
              ->label('Conseils d\'utilisation')
              ->rows(3)
              ->columnSpanFull(),
          ]),
        Section::make('Prix & stock')
          ->schema([
            TextInput::make('price')
              ->label('Prix catalogue')
              ->required()
              ->numeric()
              ->prefix('EUR')
              ->helperText('Prix de référence en euros, converti en FC/USD sur la boutique.')
              ->minValue(0),
            TextInput::make('compare_at_price')
              ->label('Prix barré')
              ->numeric()
              ->prefix('EUR')
              ->minValue(0),
            TextInput::make('stock')
              ->label('Stock')
              ->required()
              ->numeric()
              ->default(0)
              ->minValue(0),
            TextInput::make('weight')
              ->label('Poids (g)')
              ->numeric()
              ->minValue(0),
            Toggle::make('track_stock')
              ->label('Suivre le stock')
              ->default(true),
            Toggle::make('is_active')
              ->label('Actif')
              ->default(true),
            Toggle::make('is_featured')
              ->label('Vedette')
              ->default(false),
          ])
          ->columns(2),
      ]);
  }
}
