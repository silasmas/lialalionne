<?php

namespace App\Livewire\Account;

use App\Livewire\Shop\Concerns\DispatchesShopToast;
use App\Services\CurrencyService;
use App\Services\LoyaltyService;
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

  public string $deleteConfirmationText = '';

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
   * Exporte toutes les données personnelles du client (droit d'accès RGPD).
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse Téléchargement JSON
   */
  public function exportData()
  {
    $user = Auth::user()->load([
      'orders.items',
      'orders.address',
      'favoriteProducts:id,name,slug',
    ]);

    $data = [
      'compte' => [
        'nom' => $user->name,
        'email' => $user->email,
        'telephone' => $user->phone,
        'cree_le' => $user->created_at?->toIso8601String(),
      ],
      'adresse_livraison' => [
        'ligne_1' => $user->delivery_address_line_1,
        'ligne_2' => $user->delivery_address_line_2,
        'ville' => $user->delivery_city,
        'code_postal' => $user->delivery_postal_code,
        'pays' => $user->delivery_country,
      ],
      'commandes' => $user->orders->map(fn ($order) => [
        'numero' => $order->order_number,
        'statut' => $order->status?->value,
        'total' => (string) $order->total,
        'devise' => $order->currency,
        'passee_le' => $order->created_at?->toIso8601String(),
        'articles' => $order->items->map(fn ($item) => [
          'produit' => $item->product_name,
          'variante' => $item->variant_name,
          'quantite' => $item->quantity,
          'prix_unitaire' => (string) $item->unit_price,
        ])->all(),
      ])->all(),
      'favoris' => $user->favoriteProducts->pluck('name')->all(),
      'exporte_le' => now()->toIso8601String(),
    ];

    $filename = 'mes-donnees-lialalionne-' . now()->format('Y-m-d') . '.json';

    return response()->streamDownload(function () use ($data) {
      echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }, $filename, ['Content-Type' => 'application/json']);
  }

  /**
   * Efface l'erreur de validation du champ de confirmation de suppression.
   *
   * @return void
   */
  public function updatedDeleteConfirmationText(): void
  {
    $this->resetValidation('deleteConfirmationText');
  }

  /**
   * Supprime définitivement le compte client (droit à l'effacement RGPD).
   * Les commandes déjà passées sont conservées (obligation comptable) mais
   * détachées du compte ; les favoris et le panier sont supprimés.
   *
   * @return \Illuminate\Http\RedirectResponse Redirection accueil
   */
  public function deleteAccount()
  {
    $this->validate([
      'deleteConfirmationText' => ['required', 'in:SUPPRIMER'],
    ], [
      'deleteConfirmationText.in' => 'Tapez SUPPRIMER en majuscules pour confirmer.',
    ], [
      'deleteConfirmationText' => 'confirmation',
    ]);

    $user = Auth::user();
    Auth::logout();
    $user->delete();

    session()->invalidate();
    session()->regenerateToken();

    return redirect()->route('home');
  }

  /**
   * Rendu du dashboard client.
   *
   * @param CurrencyService $currencyService Service devises
   * @return \Illuminate\View\View Vue Livewire
   */
  public function render(CurrencyService $currencyService, LoyaltyService $loyaltyService)
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
      'loyaltyBalance' => $loyaltyService->balance($user),
      'loyaltyBalanceValueEur' => $loyaltyService->eurValueOfPoints($loyaltyService->balance($user)),
    ])->layout('layouts.shopwise', [
      'title' => 'Mon compte — Lialalionne',
      'noindex' => true,
    ]);
  }
}
