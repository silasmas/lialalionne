<?php

namespace App\Livewire\Account;

use App\Livewire\Shop\Concerns\DispatchesShopToast;
use App\Services\CurrencyService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/**
 * Tableau de bord espace client avec gestion de l'adresse de livraison.
 */
class DashboardPage extends Component
{
  use DispatchesShopToast;

  public string $deliveryAddressLine1 = '';

  public string $deliveryAddressLine2 = '';

  public string $deliveryCity = '';

  public string $deliveryPostalCode = '';

  public string $deliveryCountry = 'CD';

  /**
   * Charge l'adresse de livraison enregistrée dans le profil.
   *
   * @return void
   */
  public function mount(): void
  {
    $user = Auth::user();

    $this->deliveryAddressLine1 = $user->delivery_address_line_1 ?? '';
    $this->deliveryAddressLine2 = $user->delivery_address_line_2 ?? '';
    $this->deliveryCity = $user->delivery_city ?? '';
    $this->deliveryPostalCode = $user->delivery_postal_code ?? '';
    $this->deliveryCountry = $user->delivery_country ?? 'CD';
  }

  /**
   * Enregistre l'adresse de livraison dans le profil client.
   *
   * @return void
   */
  public function saveDeliveryAddress(): void
  {
    $this->validate([
      'deliveryAddressLine1' => ['required', 'string', 'max:255'],
      'deliveryAddressLine2' => ['nullable', 'string', 'max:255'],
      'deliveryCity' => ['required', 'string', 'max:255'],
      'deliveryPostalCode' => ['required', 'string', 'max:20'],
      'deliveryCountry' => ['required', 'string', 'size:2'],
    ], [
      'deliveryAddressLine1.required' => 'L\'adresse est obligatoire.',
      'deliveryCity.required' => 'La ville est obligatoire.',
      'deliveryPostalCode.required' => 'Le code postal est obligatoire.',
      'deliveryCountry.required' => 'Le pays est obligatoire.',
    ], [
      'deliveryAddressLine1' => 'adresse',
      'deliveryAddressLine2' => 'complément d\'adresse',
      'deliveryCity' => 'ville',
      'deliveryPostalCode' => 'code postal',
      'deliveryCountry' => 'pays',
    ]);

    Auth::user()->update([
      'delivery_address_line_1' => $this->deliveryAddressLine1,
      'delivery_address_line_2' => $this->deliveryAddressLine2 ?: null,
      'delivery_city' => $this->deliveryCity,
      'delivery_postal_code' => $this->deliveryPostalCode,
      'delivery_country' => $this->deliveryCountry,
    ]);

    $this->dispatchShopToast('Adresse de livraison enregistrée.', 'success');
  }

  /**
   * Efface l'erreur de validation du champ adresse.
   *
   * @return void
   */
  public function updatedDeliveryAddressLine1(): void
  {
    $this->resetValidation('deliveryAddressLine1');
  }

  /**
   * Efface l'erreur de validation du complément d'adresse.
   *
   * @return void
   */
  public function updatedDeliveryAddressLine2(): void
  {
    $this->resetValidation('deliveryAddressLine2');
  }

  /**
   * Efface l'erreur de validation de la ville.
   *
   * @return void
   */
  public function updatedDeliveryCity(): void
  {
    $this->resetValidation('deliveryCity');
  }

  /**
   * Efface l'erreur de validation du code postal.
   *
   * @return void
   */
  public function updatedDeliveryPostalCode(): void
  {
    $this->resetValidation('deliveryPostalCode');
  }

  /**
   * Efface l'erreur de validation du pays.
   *
   * @return void
   */
  public function updatedDeliveryCountry(): void
  {
    $this->resetValidation('deliveryCountry');
  }

  /**
   * Rendu du dashboard client.
   *
   * @param CurrencyService $currencyService Service devises
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CurrencyService $currencyService)
  {
    $user = Auth::user();
    $recentOrders = $user->orders()
      ->with('items')
      ->latest()
      ->limit(5)
      ->get();

    return view('livewire.account.dashboard-page', [
      'user' => $user,
      'recentOrders' => $recentOrders,
      'currencyService' => $currencyService,
    ])->layout('layouts.shopwise', [
      'title' => 'Mon compte — Lialalionne',
    ]);
  }
}
