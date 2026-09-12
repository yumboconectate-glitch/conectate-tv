@extends('layout')
@section('heading', 'Clientes')
@section('subtitle', 'Acceso IPTV, vencimientos, conexiones y credenciales')
@section('content')
<div class="grid2">
<section class="panel">
<h2>Nuevo cliente IPTV</h2>
@if($plans->isEmpty())
<div class="flash warn">Primero crea al menos un plan.</div>
@else
<form method="POST" action="{{ route('subscribers.store') }}">
@csrf
<div class="field"><label>Nombre</label><input name="name" required></div>
<div class="field"><label>Documento</label><input name="document"></div>
<div class="field"><label>Teléfono</label><input name="phone"></div>
<div class="field"><label>Correo</label><input type="email" name="email"></div>
<div class="field"><label>Plan</label><select name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}">{{ $plan->name }} · {{ $plan->channels_count }} canales</option>@endforeach</select></div>
<div class="field"><label>Vence</label><input type="datetime-local" name="expires_at"></div>
<div class="field"><label>Conexiones máximas (vacío = plan)</label><input type="number" min="1" max="20" name="max_connections"></div>
<div class="field"><label>Dispositivos máximos</label><input type="number" min="1" max="50" name="max_devices" value="5"></div>
<button class="btn" type="submit">Crear cliente</button>
</form>
@endif
</section>

<section class="panel">
<h2>Estado V0.3</h2>
<div class="card"><div class="label">Autorización</div><div class="num" style="font-size:20px">{{ strtoupper(env('ASTRA_AUTH_MODE', 'middleware')) }}</div><div class="muted" style="margin-top:7px">Conectate TV valida plan, vencimiento, canal y conexiones.</div></div>
<div class="card" style="margin-top:12px"><div class="label">Xtream API</div><div style="margin-top:8px" class="mono">{{ url('/player_api.php') }}</div><div class="muted" style="margin-top:7px">Compatibilidad inicial para apps IPTV: login, categorías, canales y M3U.</div></div>
</section>
</div>

<div class="panel" style="margin-top:18px">
<h2>Abonados</h2>
<table class="table">
<thead><tr><th>Cliente</th><th>Plan</th><th>Estado</th><th>Conexiones</th><th>Credenciales</th><th>Acciones</th></tr></thead>
<tbody>
@forelse($subscribers as $client)
<tr>
<td><strong>{{ $client->name }}</strong><div class="muted">{{ $client->document ?: 'Sin documento' }}</div><div class="muted">Vence: {{ $client->expires_at?->format('Y-m-d H:i') ?? 'Sin vencimiento' }}</div></td>
<td>{{ $client->plan?->name ?? '—' }}</td>
<td><span class="status {{ $client->statusClass() }}"><span class="s"></span>{{ $client->statusLabel() }}</span><div class="muted">{{ $client->astra_detached_at ? 'Middleware' : 'Astra interno' }}</div></td>
<td><strong>{{ $client->active_sessions_count }} / {{ $client->max_connections }}</strong><div class="muted">{{ $client->devices->count() }} dispositivos vistos</div></td>
<td>
  <div class="mono">Usuario: {{ $client->username ?: '—' }}</div>
  <div class="mono">Clave: {{ $client->access_password ?: '—' }}</div>
  <div class="muted" style="margin-top:5px">Token: {{ substr($client->token,0,8) }}••••••••</div>
</td>
<td>
<div class="actions">
  <a class="btn secondary" href="{{ route('subscribers.show', $client) }}">Ver</a>
  <a class="btn secondary" href="{{ route('subscribers.edit', $client) }}">Editar</a>
  <a class="btn secondary" target="_blank" href="{{ route('playlist', $client->token) }}">M3U</a>
  @if($client->active)
  <form method="POST" action="{{ route('subscribers.state', [$client, 'suspend']) }}">@csrf<button class="btn danger" type="submit">Suspender</button></form>
  @else
  <form method="POST" action="{{ route('subscribers.state', [$client, 'activate']) }}">@csrf<button class="btn ok" type="submit">Activar</button></form>
  @endif
  <form method="POST" action="{{ route('subscribers.rotate', $client) }}" onsubmit="return confirm('¿Rotar token y contraseña IPTV? Los accesos anteriores dejarán de funcionar.')">@csrf<button class="btn secondary" type="submit">Rotar acceso</button></form>
</div>
</td>
</tr>
@empty
<tr><td colspan="6" class="muted">Aún no hay clientes IPTV.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
