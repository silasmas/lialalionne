<?php

namespace App\Filament\Resources\AdminUsers;

use App\Filament\Resources\AdminUsers\Pages\CreateAdminUser;
use App\Filament\Resources\AdminUsers\Pages\EditAdminUser;
use App\Filament\Resources\AdminUsers\Pages\ListAdminUsers;
use App\Filament\Resources\AdminUsers\Schemas\AdminUserForm;
use App\Filament\Resources\AdminUsers\Tables\AdminUsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Ressource Filament pour gérer les utilisateurs administrateurs et leurs rôles.
 */
class AdminUserResource extends Resource
{
  protected static ?string $model = User::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

  protected static string|\UnitEnum|null $navigationGroup = 'Accès';

  protected static ?int $navigationSort = 1;

  protected static ?string $navigationLabel = 'Utilisateurs';

  protected static ?string $modelLabel = 'utilisateur';

  protected static ?string $pluralModelLabel = 'utilisateurs';

  protected static ?string $slug = 'utilisateurs';

  protected static ?string $recordTitleAttribute = 'name';

  /**
   * Configure le formulaire de création / édition.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function form(Schema $schema): Schema
  {
    return AdminUserForm::configure($schema);
  }

  /**
   * Configure la table de listing.
   *
   * @param Table $table Table Filament
   * @return Table Table configurée
   */
  public static function table(Table $table): Table
  {
    return AdminUsersTable::configure($table);
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
      'index' => ListAdminUsers::route('/'),
      'create' => CreateAdminUser::route('/create'),
      'edit' => EditAdminUser::route('/{record}/edit'),
    ];
  }

  /**
   * Limite la liste aux comptes administrateurs (équipe back-office).
   *
   * @return Builder<User>
   */
  public static function getEloquentQuery(): Builder
  {
    return parent::getEloquentQuery()->where('is_admin', true);
  }
}
