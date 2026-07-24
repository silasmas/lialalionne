<?php

namespace App\Filament\Resources\Orders\Schemas;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

/**
 * Schéma du formulaire commande admin.
 */
class OrderForm
{
  /**
   * Configure les champs du formulaire commande.
   *
   * @param Schema $schema Schéma Filament
   * @return Schema Schéma configuré
   */
  public static function configure(Schema $schema): Schema
  {
    return $schema
      ->components([
        Section::make('Commande')
          ->schema([
            TextInput::make('order_number')
              ->label('N° commande')
              ->disabled(),
            Select::make('user_id')
              ->label('Client')
              ->relationship('user', 'name')
              ->searchable()
              ->disabled(),
            Select::make('status')
              ->label('Statut')
              ->options(collect(OrderStatus::cases())->mapWithKeys(
                fn (OrderStatus $status) => [$status->value => $status->label()]
              ))
              ->required(),
            Select::make('payment_method')
              ->label('Méthode de paiement')
              ->options(collect(PaymentMethod::cases())->mapWithKeys(
                fn (PaymentMethod $method) => [$method->value => $method->label()]
              ))
              ->disabled(),
          ])
          ->columns(2),
        Section::make('Montants')
          ->description('Montants dans la devise de la commande (CDF ou USD).')
          ->schema([
            TextInput::make('currency')
              ->label('Devise')
              ->disabled(),
            TextInput::make('subtotal')
              ->label('Sous-total')
              ->numeric()
              ->disabled(),
            TextInput::make('shipping_amount')
              ->label('Livraison')
              ->numeric()
              ->disabled(),
            TextInput::make('coupon_code')
              ->label('Code promo')
              ->disabled(),
            TextInput::make('discount_amount')
              ->label('Remise')
              ->numeric()
              ->disabled(),
            TextInput::make('tax_amount')
              ->label('TVA')
              ->numeric()
              ->disabled(),
            TextInput::make('total')
              ->label('Total')
              ->numeric()
              ->disabled(),
          ])
          ->columns(2),
        Section::make('Livraison')
          ->schema([
            TextInput::make('tracking_number')
              ->label('N° de suivi')
              ->helperText('Un email est envoyé automatiquement au client lors de l\'enregistrement du numéro.')
              ->maxLength(255),
            DateTimePicker::make('shipped_at')
              ->label('Expédiée le'),
            DateTimePicker::make('delivered_at')
              ->label('Livrée le'),
            Textarea::make('notes')
              ->label('Notes internes')
              ->rows(3)
              ->columnSpanFull(),
          ])
          ->columns(2),
      ]);
  }
}
