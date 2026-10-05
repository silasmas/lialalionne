<x-mail::message>
# Merci pour votre commande !

Bonjour{{ $order->shippingAddress ? ' ' . $order->shippingAddress->first_name : '' }},

@if ($order->payment_method === \App\Enums\PaymentMethod::Cod)
Votre commande **{{ $order->order_number }}** a bien été enregistrée. Vous réglerez **{{ $currencyService->format($order->total, $order->currency) }}** en espèces à la livraison.
@else
Votre commande **{{ $order->order_number }}** a bien été enregistrée et payée.
@endif

## Récapitulatif

| | |
|---|---|
| **Total** | {{ $currencyService->format($order->total, $order->currency) }} |
| **Statut** | {{ $order->status->label() }} |

@foreach ($order->items as $item)
- {{ $item->product_name }}@if($item->variant_name) ({{ $item->variant_name }})@endif × {{ $item->quantity }} — {{ $currencyService->format($item->total_price, $order->currency) }}
@endforeach

@if ($order->shippingAddress)
## Adresse de livraison

{{ $order->shippingAddress->fullName() }}  
{{ $order->shippingAddress->address_line_1 }}  
{{ $order->shippingAddress->postal_code }} {{ $order->shippingAddress->city }}
@endif

<x-mail::button :url="url('/')">
Retourner sur la boutique
</x-mail::button>

Merci de votre confiance,  
**Lialalionne**
</x-mail::message>
