@extends('layout')
@section('heading', 'Dispositivos')
@section('subtitle', 'Equipos detectados, nombres personalizados y bloqueos')
@section('content')
<div class="panel">
<h2>Dispositivos conocidos</h2>
<table class="table">
<thead><tr><th>Dispositivo</th><th>Cliente</th><th>IP</th><th>Último canal</th><th>Última vez</th><th>Estado</th><th>Acciones</th></tr></thead>
<tbody>
@forelse($devices as $device)
<tr>
<td>
  <strong>{{ $device->displayName() }}</strong>
  @if($device->custom_name)<div class="muted">Detectado: {{ $device->device_name }}</div>@endif
  <div class="muted">{{ $device->user_agent ? \Illuminate\Support\Str::limit($device->user_agent, 50) : 'Sin User-Agent' }}</div>
  <form method="POST" action="{{ route('devices.rename', $device) }}" style="margin-top:8px;display:flex;gap:6px">@csrf @method('PUT')
    <input name="custom_name" value="{{ $device->custom_name }}" placeholder="Ej. TV Sala" style="max-width:170px">
    <button class="btn secondary" type="submit">Nombrar</button>
  </form>
</td>
<td><a href="{{ route('subscribers.show', $device->subscriber) }}" style="color:#fff"><strong>{{ $device->subscriber?->name ?? '—' }}</strong></a><div class="muted">{{ $device->subscriber?->plan?->name ?? '—' }}</div></td>
<td class="mono">{{ $device->client_ip ?? '—' }}</td>
<td>{{ $device->lastChannel?->name ?? '—' }}</td>
<td>{{ $device->last_seen_at?->diffForHumans() ?? '—' }}</td>
<td>
  @if($device->blocked)<span class="status off"><span class="s"></span>BLOQUEADO</span>
  @elseif($device->ip_blocked)<span class="status off"><span class="s"></span>IP BLOQUEADA</span>
  @else<span class="status on"><span class="s"></span>PERMITIDO</span>@endif
</td>
<td>
<div class="actions">
<form method="POST" action="{{ route('devices.block', $device) }}">@csrf<button class="btn {{ $device->blocked ? 'ok' : 'danger' }}" type="submit">{{ $device->blocked ? 'Habilitar equipo' : 'Bloquear equipo' }}</button></form>
<form method="POST" action="{{ route('devices.ip-block', $device) }}">@csrf<button class="btn {{ $device->ip_blocked ? 'ok' : 'secondary' }}" type="submit">{{ $device->ip_blocked ? 'Habilitar IP' : 'Bloquear IP' }}</button></form>
<form method="POST" action="{{ route('devices.close', $device) }}">@csrf<button class="btn secondary" type="submit">Cerrar sesiones</button></form>
</div>
</td>
</tr>
@empty
<tr><td colspan="7" class="muted">Todavía no se han detectado dispositivos.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
