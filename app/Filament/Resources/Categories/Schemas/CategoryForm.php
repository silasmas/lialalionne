<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

/**
 * Schéma du formulaire catégorie admin.
 */
class CategoryForm
{
  /**
   * Configure les champs du formulaire catégorie.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Informations')
          ->schema([
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
            Textarea::make('description')
              ->label('Description')
              ->rows(4)
              ->columnSpanFull(),
            FileUpload::make('image')
              ->label('Image')
              ->image()
              ->disk('public')
              ->directory('categories')
              ->visibility('public'),
            Toggle::make('is_active')
              ->label('Active')
              ->default(true),
            TextInput::make('sort_order')
              ->label('Ordre d\'affichage')
              ->numeric()
              ->default(0)
              ->minValue(0),
          ])
          ->columns(2),
      ]);
  }
}
