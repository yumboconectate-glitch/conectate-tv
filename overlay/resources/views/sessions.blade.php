@extends('layout')
@section('heading', 'Sesiones')
@section('subtitle', 'Conexiones HTTP/HLS reportadas por Astra')
@section('content')
@if($error)<div class="flash warn">Astra sessions: {{ $error }}</div>@endif
<section class="cards">
  <div class="card"><div class="label">Activas ahora</div><div class="num cyan">{{ $active->count() }}</div></div>
  <div class="card"><div class="label">Identificadas</div><div class="num">{{ $active->whereNotNull('subscriber_id')->count() }}</div></div>
  <div class="card"><div class="label">Sin identificar</div><div class="num">{{ $active->whereNull('subscriber_id')->count() }}</div></div>
  <div class="card"><div class="label">Cerradas 24h</div><div class="num">{{ $recent->count() }}</div></div>
  <div class="card"><div class="label">Fuente</div><div class="num" style="font-size:18px">ASTRA API</div></div>
</section>

<div class="panel">
<h2>Sesiones activas</h2>
<table class="table">
<thead><tr><th>Sesión</th><th>Cliente</th><th>Canal</th><th>IP</th><th>Dispositivo</th><th>Tiempo</th><th></th></tr></thead>
<tbody>
@forelse($active as $session)
<tr>
<td class="mono">{{ $session->astra_session_id }}</td>
<td><strong>{{ $session->subscriber?->name ?? 'No identificado' }}</strong><div class="muted">{{ $session->subscriber?->plan?->name ?? '—' }}</div></td>
<td>{{ $session->channel?->name ?? '—' }}<div class="muted mono">{{ $session->channel?->astra_stream_id ?? '—' }}</div></td>
<td class="mono">{{ $session->client_ip ?? '—' }}</td>
<td><span class="muted">{{ $session->user_agent ? \Illuminate\Support\Str::limit($session->user_agent, 42) : '—' }}</span></td>
<td>{{ gmdate('H:i:s', max(0, (int)$session->uptime)) }}</td>
<td><form method="POST" action="{{ route('sessions.close', $session->astra_session_id) }}" onsubmit="return confirm('¿Cerrar esta sesión en Astra?')">@csrf<button class="btn danger" type="submit">Cerrar</button></form></td>
</tr>
@empty
<tr><td colspan="7" class="muted">No hay sesiones activas.</td></tr>
@endforelse
</tbody>
</table>
</div>

<div class="panel" style="margin-top:18px">
<h2>Cerradas recientemente</h2>
<table class="table">
<thead><tr><th>Cliente</th><th>Canal</th><th>IP</th><th>Inicio</th><th>Cierre</th></tr></thead>
<tbody>
@forelse($recent as $session)
<tr><td>{{ $session->subscriber?->name ?? 'No identificado' }}</td><td>{{ $session->channel?->name ?? '—' }}</td><td class="mono">{{ $session->client_ip ?? '—' }}</td><td>{{ $session->first_seen_at?->format('Y-m-d H:i:s') ?? '—' }}</td><td>{{ $session->closed_at?->format('Y-m-d H:i:s') ?? '—' }}</td></tr>
@empty
<tr><td colspan="5" class="muted">Sin historial reciente.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
