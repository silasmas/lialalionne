<?php

namespace App\Filament\Resources\AdminUsers\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Password;

/**
 * Schéma du formulaire utilisateurs administrateurs (identité, mot de passe, rôles).
 */
class AdminUserForm
{
  /**
   * Configure les champs du formulaire utilisateur admin.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Identité')
          ->schema([
            TextInput::make('name')
              ->label('Nom')
              ->required()
              ->maxLength(255),
            TextInput::make('email')
              ->label('Email')
              ->email()
              ->required()
              ->unique(ignoreRecord: true)
              ->maxLength(255),
            TextInput::make('phone')
              ->label('Téléphone')
              ->tel()
              ->maxLength(30),
            Toggle::make('is_admin')
              ->label('Accès panel admin')
              ->helperText('Doit rester activé pour les comptes de cette liste.')
              ->default(true)
              ->required(),
          ])
          ->columns(2),
        Section::make('Sécurité')
          ->schema([
            TextInput::make('password')
              ->label('Mot de passe')
              ->password()
              ->revealable()
              ->rule(Password::defaults())
              ->dehydrated(fn (?string $state): bool => filled($state))
              ->required(fn (string $operation): bool => $operation === 'create')
              ->confirmed(),
            TextInput::make('password_confirmation')
              ->label('Confirmation mot de passe')
              ->password()
              ->revealable()
              ->dehydrated(false)
              ->required(fn (string $operation): bool => $operation === 'create'),
          ])
          ->columns(2),
        Section::make('Rôles Shield')
          ->description('Les permissions effectives dépendent des rôles assignés (gérés via Rôles & permissions).')
          ->schema([
            CheckboxList::make('roles')
              ->label('Rôles')
              ->relationship('roles', 'name')
              ->searchable()
              ->bulkToggleable()
              ->columns(2)
              ->helperText('Ex. super_admin = accès total via Gate.'),
          ]),
      ]);
  }
}
