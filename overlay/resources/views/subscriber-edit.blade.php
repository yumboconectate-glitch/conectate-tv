@extends('layout')
@section('heading', 'Editar cliente')
@section('subtitle', $subscriber->name)
@section('content')
<div class="grid2">
<section class="panel">
<h2>Datos y servicio</h2>
<form method="POST" action="{{ route('subscribers.update', $subscriber) }}">
@csrf @method('PUT')
<div class="field"><label>Nombre</label><input name="name" value="{{ $subscriber->name }}" required></div>
<div class="field"><label>Documento</label><input name="document" value="{{ $subscriber->document }}"></div>
<div class="field"><label>Teléfono</label><input name="phone" value="{{ $subscriber->phone }}"></div>
<div class="field"><label>Correo</label><input type="email" name="email" value="{{ $subscriber->email }}"></div>
<div class="field"><label>Plan</label><select name="plan_id" required>@foreach($plans as $plan)<option value="{{ $plan->id }}" {{ $subscriber->plan_id === $plan->id ? 'selected' : '' }}>{{ $plan->name }}</option>@endforeach</select></div>
<div class="field"><label>Vence</label><input type="datetime-local" name="expires_at" value="{{ $subscriber->expires_at?->format('Y-m-d\TH:i') }}"></div>
<div class="field"><label>Conexiones máximas simultáneas</label><input type="number" min="1" max="20" name="max_connections" value="{{ $subscriber->max_connections }}" required></div>
<div class="field"><label>Dispositivos máximos registrados</label><input type="number" min="1" max="50" name="max_devices" value="{{ $subscriber->max_devices ?? 5 }}" required></div>
<div class="actions"><button class="btn" type="submit">Guardar cambios</button><a class="btn secondary" href="{{ route('subscribers.show', $subscriber) }}">Volver</a></div>
</form>
</section>
<section class="panel">
<h2>Credenciales IPTV</h2>
<div class="card"><div class="label">Xtream</div><div class="mono" style="margin-top:8px">Servidor: {{ url('/') }}</div><div class="mono">Usuario: {{ $subscriber->username }}</div><div class="mono">Contraseña: {{ $subscriber->access_password }}</div></div>
<div class="card" style="margin-top:12px"><div class="label">M3U directo</div><div class="mono" style="word-break:break-all;margin-top:8px">{{ route('playlist', $subscriber->token) }}</div></div>
<div class="card" style="margin-top:12px"><div class="label">Xtream endpoints</div><div class="mono" style="word-break:break-all;margin-top:8px">{{ url('/player_api.php') }}</div><div class="mono">{{ url('/get.php') }}</div><div class="mono">{{ url('/xmltv.php') }}</div></div>
<div class="flash warn" style="margin-top:12px">Estas credenciales dan acceso al servicio. No las compartas fuera del abonado correspondiente.</div>
</section>
</div>
@endsection
