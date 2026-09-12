@extends('layout')
@section('heading', $subscriber->name)
@section('subtitle', 'Ficha IPTV · servicio, dispositivos y sesiones')
@section('content')
<section class="cards">
  <div class="card"><div class="label">Estado</div><div class="num" style="font-size:18px">{{ $subscriber->statusLabel() }}</div></div>
  <div class="card"><div class="label">Plan</div><div class="num" style="font-size:18px">{{ $subscriber->plan?->name ?? '—' }}</div></div>
  <div class="card"><div class="label">Conexiones</div><div class="num">{{ $activeSessions->count() }}/{{ $subscriber->max_connections }}</div></div>
  <div class="card"><div class="label">Dispositivos</div><div class="num">{{ $subscriber->devices->count() }}/{{ $subscriber->max_devices ?? 5 }}</div></div>
  <div class="card"><div class="label">Vencimiento</div><div class="num" style="font-size:16px">{{ $subscriber->expires_at?->format('Y-m-d H:i') ?? 'Sin vencimiento' }}</div></div>
</section>

<div class="actions" style="margin-bottom:18px">
<a class="btn" href="{{ route('subscribers.edit', $subscriber) }}">Editar cliente</a>
<a class="btn secondary" target="_blank" href="{{ route('playlist', $subscriber->token) }}">M3U</a>
<a class="btn secondary" href="{{ route('devices.index') }}">Dispositivos</a>
<a class="btn secondary" href="{{ route('sessions.index') }}">Sesiones</a>
</div>

<div class="grid2">
<section class="panel">
<h2>Sesiones activas</h2>
<table class="table">
<thead><tr><th>Canal</th><th>IP</th><th>Dispositivo</th><th>Tiempo</th></tr></thead>
<tbody>
@forelse($activeSessions as $session)
<tr><td>{{ $session->channel?->name ?? '—' }}</td><td class="mono">{{ $session->client_ip ?? '—' }}</td><td>{{ $session->user_agent ? \Illuminate\Support\Str::limit($session->user_agent, 35) : '—' }}</td><td>{{ gmdate('H:i:s', max(0,(int)$session->uptime)) }}</td></tr>
@empty
<tr><td colspan="4" class="muted">Sin sesiones activas.</td></tr>
@endforelse
</tbody>
</table>
</section>

<section class="panel">
<h2>Dispositivos</h2>
<table class="table">
<thead><tr><th>Nombre</th><th>IP</th><th>Estado</th><th>Última vez</th></tr></thead>
<tbody>
@forelse($subscriber->devices->sortByDesc('last_seen_at') as $device)
<tr><td>{{ $device->displayName() }}</td><td class="mono">{{ $device->client_ip ?? '—' }}</td><td>{{ $device->blocked ? 'BLOQUEADO' : ($device->ip_blocked ? 'IP BLOQUEADA' : 'OK') }}</td><td>{{ $device->last_seen_at?->diffForHumans() ?? '—' }}</td></tr>
@empty
<tr><td colspan="4" class="muted">Sin dispositivos registrados.</td></tr>
@endforelse
</tbody>
</table>
</section>
</div>

<div class="panel" style="margin-top:18px">
<h2>Historial de sesiones</h2>
<table class="table">
<thead><tr><th>Canal</th><th>IP</th><th>Inicio</th><th>Cierre</th><th>Dispositivo</th></tr></thead>
<tbody>
@forelse($history as $session)
<tr><td>{{ $session->channel?->name ?? '—' }}</td><td class="mono">{{ $session->client_ip ?? '—' }}</td><td>{{ $session->first_seen_at?->format('Y-m-d H:i:s') ?? '—' }}</td><td>{{ $session->closed_at?->format('Y-m-d H:i:s') ?? '—' }}</td><td>{{ $session->user_agent ? \Illuminate\Support\Str::limit($session->user_agent, 45) : '—' }}</td></tr>
@empty
<tr><td colspan="5" class="muted">Sin historial todavía.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
