<?php

namespace App\Filament\Resources\BotConversations\Tables;

use App\Models\BotConversation;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Liste des conversations : statut IA / équipe, raison du transfert,
 * journal complet consultable (relecture hebdomadaire des consignes).
 */
class BotConversationsTable
{
  public static function configure(Table $table): Table
  {
    return $table
      ->defaultSort('last_message_at', 'desc')
      ->modifyQueryUsing(fn ($query) => $query->with('user')->withCount('messages'))
      ->columns([
        TextColumn::make('phone')
          ->label('WhatsApp')
          ->formatStateUsing(fn (string $state) => '+' . $state)
          ->url(fn (BotConversation $record) => 'https://wa.me/' . $record->phone, true)
          ->searchable(),
        TextColumn::make('user.name')
          ->label('Cliente')
          ->placeholder('Nouvelle')
          ->searchable(),
        TextColumn::make('status')
          ->label('Géré par')
          ->badge()
          ->formatStateUsing(fn (string $state) => $state === BotConversation::STATUS_HUMAN ? 'Équipe' : 'IA')
          ->color(fn (string $state) => $state === BotConversation::STATUS_HUMAN ? 'warning' : 'success'),
        TextColumn::make('handoff_reason')
          ->label('Raison du transfert')
          ->limit(40)
          ->placeholder('—')
          ->wrap(),
        TextColumn::make('messages_count')
          ->label('Messages')
          ->sortable(),
        TextColumn::make('last_message_at')
          ->label('Dernier message')
          ->since()
          ->sortable(),
      ])
      ->filters([
        SelectFilter::make('status')
          ->label('Géré par')
          ->options([
            BotConversation::STATUS_BOT => 'IA',
            BotConversation::STATUS_HUMAN => 'Équipe',
          ]),
      ])
      ->recordActions([
        Action::make('journal')
          ->label('Lire')
          ->icon('heroicon-o-eye')
          ->modalHeading(fn (BotConversation $record) => 'Conversation +' . $record->phone)
          ->modalContent(fn (BotConversation $record) => view('filament.bot.conversation', [
            'conversation' => $record,
            'messages' => $record->messages()->latest('id')->limit(200)->get()->reverse(),
          ]))
          ->modalSubmitAction(false)
          ->modalCancelActionLabel('Fermer')
          ->modalWidth('3xl'),
        Action::make('backToAi')
          ->label('Rendre à l\'IA')
          ->icon('heroicon-o-sparkles')
          ->color('success')
          ->visible(fn (BotConversation $record) => $record->status === BotConversation::STATUS_HUMAN)
          ->requiresConfirmation()
          ->modalDescription('L\'IA reprendra cette conversation au prochain message de la cliente (pensez aussi à redémarrer le robot dans Callbell).')
          ->action(fn (BotConversation $record) => $record->forceFill([
            'status' => BotConversation::STATUS_BOT,
            'handoff_reason' => null,
            'handed_off_at' => null,
          ])->save()),
      ]);
  }
}
