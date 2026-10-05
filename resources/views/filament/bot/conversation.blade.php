{{-- Journal d'une conversation WhatsApp avec la conseillère IA. --}}
<div style="display:flex; flex-direction:column; gap:10px; max-height:65vh; overflow-y:auto; font-size:14px;">
  @if ($conversation->handoff_reason)
    <div style="padding:8px 12px; border-radius:8px; background:rgba(245,158,11,.12);">
      <strong>Transférée à l'équipe</strong> {{ $conversation->handed_off_at?->diffForHumans() }} — {{ $conversation->handoff_reason }}
    </div>
  @endif

  @forelse ($messages as $message)
    @if ($message->role === 'tool')
      <details style="align-self:center; width:90%; font-size:12px; opacity:.8;">
        <summary>
          ⚙️ Outil <code>{{ $message->content }}</code>
          @if ($message->meta['is_error'] ?? false) <span style="color:#dc2626;">(erreur)</span> @endif
          · {{ $message->created_at->format('d/m H:i') }}
        </summary>
        <pre style="white-space:pre-wrap; word-break:break-word; font-size:11px; margin-top:4px;">Entrée : {{ json_encode($message->meta['input'] ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) }}

Résultat : {{ $message->meta['result'] ?? '' }}</pre>
      </details>
    @else
      @php($isClient = $message->role === 'user')
      <div style="align-self:{{ $isClient ? 'flex-start' : 'flex-end' }}; max-width:80%; padding:8px 12px; border-radius:12px; background:{{ $isClient ? 'rgba(127,127,127,.12)' : 'rgba(37,211,102,.15)' }};">
        <div style="font-size:11px; opacity:.7; margin-bottom:2px;">{{ $isClient ? 'Cliente' : 'Conseillère IA' }} · {{ $message->created_at->format('d/m H:i') }}@if (($message->meta['etat'] ?? null) === 'rx_humain') · transfert @endif</div>
        <div style="white-space:pre-line;">{{ $message->content }}</div>
      </div>
    @endif
  @empty
    <p>Aucun message.</p>
  @endforelse
</div>
