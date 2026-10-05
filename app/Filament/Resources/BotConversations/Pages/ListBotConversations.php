<?php

namespace App\Filament\Resources\BotConversations\Pages;

use App\Filament\Resources\BotConversations\BotConversationResource;
use Filament\Resources\Pages\ListRecords;

class ListBotConversations extends ListRecords
{
  protected static string $resource = BotConversationResource::class;
}
