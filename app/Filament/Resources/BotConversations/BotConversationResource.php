<?php

namespace App\Filament\Resources\BotConversations;

use App\Filament\Resources\BotConversations\Pages\ListBotConversations;
use App\Filament\Resources\BotConversations\Tables\BotConversationsTable;
use App\Models\BotConversation;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

/**
 * Relecture des conversations WhatsApp de la conseillère IA.
 */
class BotConversationResource extends Resource
{
  protected static ?string $model = BotConversation::class;

  protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

  protected static string | \UnitEnum | null $navigationGroup = 'Marketing';

  protected static ?int $navigationSort = 3;

  protected static ?string $navigationLabel = 'Conversations WhatsApp';

  protected static ?string $modelLabel = 'conversation';

  protected static ?string $pluralModelLabel = 'conversations WhatsApp';

  public static function table(Table $table): Table
  {
    return BotConversationsTable::configure($table);
  }

  /**
   * Nombre de conversations transférées à l'équipe.
   *
   * @return string|null Badge
   */
  public static function getNavigationBadge(): ?string
  {
    $count = static::getModel()::where('status', BotConversation::STATUS_HUMAN)->count();

    return $count > 0 ? (string) $count : null;
  }

  public static function getNavigationBadgeColor(): ?string
  {
    return 'warning';
  }

  public static function canCreate(): bool
  {
    return false;
  }

  public static function getPages(): array
  {
    return [
      'index' => ListBotConversations::route('/'),
    ];
  }
}
