<?php

namespace App\Filament\Resources\HomeSlides\Schemas;

use App\Enums\SlideMediaType;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

/**
 * Schéma du formulaire slide d'accueil (photo ou vidéo d'animation).
 */
class HomeSlideForm
{
  /**
   * Configure les champs du formulaire slide.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Média')
          ->description('Chaque slide peut être une photo ou une vidéo d\'animation en boucle.')
          ->schema([
            Select::make('media_type')
              ->label('Type de slide')
              ->options(collect(SlideMediaType::cases())->mapWithKeys(
                fn (SlideMediaType $type) => [$type->value => $type->label()]
              )->all())
              ->default(SlideMediaType::Image->value)
              ->required()
              ->native(false)
              ->live(),
            Group::make([
              FileUpload::make('image_path')
                ->label('Photo')
                ->image()
                ->disk('public')
                ->directory('home-slides')
                ->visibility('public')
                ->maxSize(5120)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->required()
                ->helperText('JPG, PNG ou WebP — 5 Mo maximum.'),
            ])
              ->visible(fn (Get $get): bool => self::isImageType($get('media_type'))),
            Group::make([
              FileUpload::make('video_path')
                ->label('Vidéo d\'animation')
                ->disk('public')
                ->directory('home-slides')
                ->visibility('public')
                ->maxSize(30720)
                ->acceptedFileTypes([
                  'video/mp4',
                  'video/webm',
                  'video/quicktime',
                  'video/x-m4v',
                ])
                ->required()
                ->helperText('MP4 ou WebM recommandé, 30 Mo max. Lecture automatique, muette et en boucle sur l\'accueil.'),
              FileUpload::make('poster_path')
                ->label('Image d\'attente (poster)')
                ->image()
                ->disk('public')
                ->directory('home-slides')
                ->visibility('public')
                ->maxSize(5120)
                ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                ->helperText('Affichée avant le démarrage de la vidéo, ou si la lecture auto est bloquée.'),
            ])
              ->visible(fn (Get $get): bool => self::isVideoType($get('media_type'))),
          ]),
        Section::make('Texte & bouton')
          ->schema([
            TextInput::make('kicker')
              ->label('Sur-titre')
              ->maxLength(120)
              ->helperText('Petite ligne au-dessus du titre (ex. « Cure détox 100% naturelle »).'),
            TextInput::make('title')
              ->label('Titre')
              ->required()
              ->maxLength(160),
            TextInput::make('button_label')
              ->label('Libellé du bouton')
              ->maxLength(80)
              ->helperText('Laisser vide pour masquer le bouton.'),
            TextInput::make('button_url')
              ->label('Lien du bouton')
              ->maxLength(255)
              ->helperText('Chemin interne (/boutique) ou URL complète (https://…).'),
            TextInput::make('sort_order')
              ->label('Ordre d\'affichage')
              ->numeric()
              ->default(0)
              ->minValue(0),
            Toggle::make('is_active')
              ->label('Visible sur l\'accueil')
              ->default(true),
          ])
          ->columns(2),
      ]);
  }

  /**
   * Indique si le type de média sélectionné est une photo.
   *
   * @param mixed $mediaType Valeur brute du formulaire
   * @return bool True si photo
   */
  private static function isImageType(mixed $mediaType): bool
  {
    return self::mediaTypeValue($mediaType) === SlideMediaType::Image->value;
  }

  /**
   * Indique si le type de média sélectionné est une vidéo.
   *
   * @param mixed $mediaType Valeur brute du formulaire
   * @return bool True si vidéo
   */
  private static function isVideoType(mixed $mediaType): bool
  {
    return self::mediaTypeValue($mediaType) === SlideMediaType::Video->value;
  }

  /**
   * Normalise la valeur du type de média (enum ou chaîne).
   *
   * @param mixed $mediaType Valeur brute du formulaire
   * @return string Valeur enum
   */
  private static function mediaTypeValue(mixed $mediaType): string
  {
    if ($mediaType instanceof SlideMediaType) {
      return $mediaType->value;
    }

    return (string) $mediaType;
  }
}
