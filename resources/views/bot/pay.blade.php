<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="robots" content="noindex, nofollow">
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('assets/favicon-32.png') }}">
  <title>Paiement de votre commande — Lialalionne</title>
  <style>
    :root { --ink:#1d1a16; --muted:#6b6457; --line:#e8e1d2; --bg:#faf7f0; --brand:#C5A059; --brand-dark:#734f08; --ok:#2f7d4f; --err:#b3261e; }
    * { box-sizing: border-box; }
    body { margin:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; background:var(--bg); color:var(--ink); }
    main { max-width: 480px; margin: 0 auto; padding: 24px 16px 48px; }
    .brand { background:#000; color: var(--brand); font-weight: 700; letter-spacing: .08em; text-transform: uppercase; text-align:center; padding: 14px; border-radius: 14px; margin-bottom: 16px; font-size: .9rem; }
    .card { background:#fff; border:1px solid var(--line); border-radius: 14px; padding: 18px; margin-bottom: 16px; }
    h1 { font-size: 1.25rem; margin: 0 0 4px; }
    .muted { color: var(--muted); font-size: .92rem; }
    table { width:100%; border-collapse: collapse; font-size: .95rem; }
    td { padding: 6px 0; vertical-align: top; }
    td.r { text-align: right; white-space: nowrap; }
    tr.total td { border-top: 1px solid var(--line); padding-top: 10px; font-weight: 700; font-size: 1.05rem; }
    label { display:block; font-size: .9rem; margin: 12px 0 6px; font-weight: 600; }
    select, input { width:100%; padding: 12px; border:1px solid var(--line); border-radius: 10px; font-size: 1rem; background:#fff; }
    button { width:100%; margin-top: 14px; padding: 14px; border:0; border-radius: 10px; font-size: 1rem; font-weight: 600; cursor:pointer; background: #000; color: var(--brand); }
    button.secondary { background:#fff; color: var(--brand-dark); border:1px solid var(--brand); }
    .notice { padding: 12px 14px; border-radius: 10px; margin-bottom: 16px; font-size: .95rem; }
    .notice.ok { background:#e8f4ec; color: var(--ok); }
    .notice.err { background:#fbeaea; color: var(--err); }
    .notice.info { background:#fef9e7; color: var(--brand-dark); }
    .center { text-align:center; }
  </style>
</head>
<body>
<main>
  <div class="brand">Chez Lia — Lialalionne</div>

  @if ($state === 'invalid')
    <div class="card center">
      <h1>Lien introuvable</h1>
      <p class="muted">Ce lien de paiement n'existe pas. Écrivez-nous sur WhatsApp, nous vous en renvoyons un.</p>
    </div>
  @else
    @if (session('status'))
      <div class="notice info">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
      <div class="notice err">{{ $errors->first() }}</div>
    @endif

    @if ($state === 'paid')
      <div class="notice ok">Merci ! Votre commande {{ $order->order_number }} est confirmée ({{ $order->status->label() }}). Nous vous tenons informée sur WhatsApp.</div>
    @elseif ($state === 'cancelled')
      <div class="notice err">Cette commande a été annulée. Écrivez-nous sur WhatsApp si vous souhaitez la reprendre.</div>
    @elseif ($state === 'expired')
      <div class="notice err">Ce lien a expiré. Écrivez-nous sur WhatsApp, nous vous en renvoyons un nouveau.</div>
    @endif

    <div class="card">
      <h1>Commande {{ $order->order_number }}</h1>
      <p class="muted">{{ $order->fulfillment_type === 'pickup' ? 'Retrait en boutique' : 'Livraison' }}</p>
      @php($fmt = app(\App\Services\CurrencyService::class))
      <table>
        @foreach ($order->items as $item)
          <tr>
            <td>{{ $item->quantity }} × {{ $item->product_name }}@if ($item->variant_name) ({{ $item->variant_name }})@endif</td>
            <td class="r">{{ $fmt->formatOrderAmount($item->total_price, $order->currency) }}</td>
          </tr>
        @endforeach
        @if ((float) $order->shipping_amount > 0)
          <tr><td>Livraison</td><td class="r">{{ $fmt->formatOrderAmount($order->shipping_amount, $order->currency) }}</td></tr>
        @endif
        @if ((float) $order->discount_amount > 0)
          <tr><td>Remise</td><td class="r">− {{ $fmt->formatOrderAmount($order->discount_amount, $order->currency) }}</td></tr>
        @endif
        <tr class="total"><td>Total</td><td class="r">{{ $fmt->formatOrderAmount($order->total, $order->currency) }}</td></tr>
      </table>
    </div>

    @if ($state === 'pending')
      @if ($cardEnabled)
        <div class="card">
          <h1>Payer par carte bancaire</h1>
          <p class="muted">Vous serez redirigée vers la page de paiement sécurisée.</p>
          <form method="POST" action="{{ route('bot.pay.card', ['token' => $token]) }}">
            @csrf
            <button type="submit">Payer {{ $fmt->formatOrderAmount($order->total, $order->currency) }} par carte</button>
          </form>
        </div>
      @else
        <div class="notice err">Le paiement par carte n'est pas disponible pour le moment. Répondez à notre conversation WhatsApp pour payer par Mobile Money.</div>
      @endif
    @endif

    <p class="muted center">Une question ? Répondez simplement à notre conversation WhatsApp.</p>
  @endif
</main>
</body>
</html>
