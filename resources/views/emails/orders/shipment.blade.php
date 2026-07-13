<x-mail::message>
# Votre commande est en route !

Bonjour{{ $order->shippingAddress ? ' ' . $order->shippingAddress->first_name : '' }},

Bonne nouvelle : votre commande **{{ $order->order_number }}** a été expédiée.

## Numéro de suivi

**{{ $order->tracking_number }}**

@if ($order->shipped_at)
Expédiée le {{ $order->shipped_at->format('d/m/Y à H:i') }}.
@endif

@if ($order->shippingAddress)
## Adresse de livraison

{{ $order->shippingAddress->fullName() }}  
{{ $order->shippingAddress->address_line_1 }}  
{{ $order->shippingAddress->postal_code }} {{ $order->shippingAddress->city }}
@endif

<x-mail::button :url="route('account.orders.show', $order)">
Suivre ma commande
</x-mail::button>

Merci de votre confiance,  
**Lialalionne**
</x-mail::message>
