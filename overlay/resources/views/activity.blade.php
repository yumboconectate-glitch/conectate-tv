@extends('layout')
@section('heading', 'Actividad')
@section('subtitle', 'Auditoría de clientes, dispositivos y API Conectate OSS')
@section('content')
<div class="panel">
<table class="table">
<thead><tr><th>Fecha</th><th>Acción</th><th>Cliente</th><th>Actor</th><th>IP</th><th>Detalle</th></tr></thead>
<tbody>
@forelse($logs as $log)
<tr>
<td>{{ $log->created_at?->format('Y-m-d H:i:s') }}</td>
<td class="mono cyan">{{ $log->action }}</td>
<td>{{ $log->subscriber?->name ?? '—' }}</td>
<td>{{ $log->actor ?? '—' }}</td>
<td class="mono">{{ $log->ip ?? '—' }}</td>
<td class="mono muted">{{ $log->meta ? \Illuminate\Support\Str::limit(json_encode($log->meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES), 100) : '—' }}</td>
</tr>
@empty
<tr><td colspan="6" class="muted">Todavía no hay actividad registrada.</td></tr>
@endforelse
</tbody>
</table>
</div>
@endsection
