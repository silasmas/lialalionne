<?php

namespace App\Filament\Resources\Orders\Actions;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Services\BotCommerceService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

/**
 * « Prête au retrait » : l'agent a mis la commande de côté. La commande passe
 * à « Expédiée » (= prête pour un retrait) et la cliente WhatsApp reçoit
 * automatiquement l'adresse de la boutique et le montant à régler.
 */
class ReadyForPickupAction
{
  /**
   * @return Action Action de table ou d'en-tête
   */
  public static function make(): Action
  {
    return Action::make('readyForPickup')
      ->label('Prête au retrait')
      ->icon('heroicon-o-shopping-bag')
      ->color('success')
      ->visible(fn (Order $record): bool => self::isEligible($record))
      ->requiresConfirmation()
      ->modalHeading('Commande prête au retrait ?')
      ->modalDescription('Les produits sont mis de côté. La cliente est prévenue sur WhatsApp qu\'elle peut passer en boutique.')
      ->modalSubmitActionLabel('Oui, prévenir la cliente')
      ->action(function (Order $record): void {
        $record->update([
          'status' => OrderStatus::Shipped,
          'shipped_at' => $record->shipped_at ?? now(),
        ]);

        Notification::make()
          ->success()
          ->title('Commande ' . $record->order_number . ' prête : cliente prévenue')
          ->send();
      });
  }

  /**
   * Retrait en boutique, pas encore prête : à payer sur place, ou déjà payée.
   *
   * @param Order $order Commande
   * @return bool True si l'action s'applique
   */
  public static function isEligible(Order $order): bool
  {
    if ($order->fulfillment_type !== 'pickup') {
      return false;
    }

    if (app(BotCommerceService::class)->isInStorePayment($order)) {
      return in_array($order->status, [OrderStatus::Pending, OrderStatus::Processing], true);
    }

    return in_array($order->status, [OrderStatus::Paid, OrderStatus::Processing], true);
  }
}
