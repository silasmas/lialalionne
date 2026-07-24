<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ressource Filament pour consulter les clients (utilisateurs non admin).
 */
class UserResource extends Resource
{
  protected static ?string $model = User::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

  protected static string | \UnitEnum | null $navigationGroup = 'Ventes';

  protected static ?int $navigationSort = 3;

  protected static ?string $navigationLabel = 'Clients';

  protected static ?string $modelLabel = 'client';

  protected static ?string $pluralModelLabel = 'clients';

  protected static ?string $recordTitleAttribute = 'name';

  /**
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return UserForm::configure($schema);
  }

  /**
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return UsersTable::configure($table);
  }

  /**
   * @return array<int, string>
   */
  public static function getRelations(): array
  {
    return [];
  }

  /**
   * @return array<string, \Filament\Resources\Pages\PageRegistration>
   */
  public static function getPages(): array
  {
    return [
      'index' => ListUsers::route('/'),
      'edit' => EditUser::route('/{record}/edit'),
    ];
  }

  /**
   * Filtre les administrateurs — affiche uniquement les clients.
   *
   * @return Builder<User>
   */
  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()->where('is_admin', false);
  }

  /**
   * @return bool
   */
  public static function canCreate(): bool
  {
    return false;
  }
}
