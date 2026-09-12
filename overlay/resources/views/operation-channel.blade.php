@extends('layout')
@section('heading',$channel->publicName())
@section('subtitle','Detalle operativo, disponibilidad e historial de incidentes')
@section('content')

<style>
.detail-head{display:flex;align-items:center;gap:16px;margin-bottom:18px}
.detail-logo{width:76px;height:76px;object-fit:contain;border-radius:14px;background:#071526;border:1px solid rgba(90,160,230,.28);padding:5px}
.detail-errors{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
@media(max-width:800px){.detail-errors{grid-template-columns:1fr}}
</style>

<div class="detail-head">
    @if($channel->logo_url)
        <img class="detail-logo" src="{{ $channel->logo_url }}" alt="">
    @endif
    <div>
        <h2 style="margin:0">{{ $channel->publicName() }}</h2>
        <div class="mono muted">{{ $channel->astra_stream_id }}</div>
        <div style="margin-top:8px">
            <span class="status {{ $channel->on_air ? 'on' : 'off' }}">
                {{ $channel->on_air ? 'ON AIR' : 'OFFLINE' }}
            </span>
        </div>
    </div>
</div>

<section class="cards">
    <div class="card"><div class="label">UPTIME 24H</div><div class="num">{{ number_format((float)data_get($metrics,'uptime_24h.percent',100),3) }}%</div></div>
    <div class="card"><div class="label">UPTIME 7D</div><div class="num">{{ number_format((float)data_get($metrics,'uptime_7d.percent',100),3) }}%</div></div>
    <div class="card"><div class="label">UPTIME 30D</div><div class="num">{{ number_format((float)data_get($metrics,'uptime_30d.percent',100),3) }}%</div></div>
    <div class="card"><div class="label">BITRATE</div><div class="num" style="font-size:18px">{{ $channel->bitrate ? number_format($channel->bitrate/1000,2).' Mb/s' : '—' }}</div></div>
    <div class="card"><div class="label">SESIONES</div><div class="num">{{ (int)$channel->sessions }}</div></div>
</section>

<div class="detail-errors" style="margin-bottom:18px">
    <div class="card"><div class="label">CC ERRORS</div><div class="num">{{ (int)$channel->cc_errors }}</div></div>
    <div class="card"><div class="label">PES ERRORS</div><div class="num">{{ (int)$channel->pes_errors }}</div></div>
    <div class="card"><div class="label">SCRAMBLING</div><div class="num">{{ (int)$channel->scrambling_errors }}</div></div>
</div>

<div class="actions" style="margin-bottom:18px">
    <a class="btn secondary" href="{{ route('operations.index') }}">Volver a Operacion IPTV</a>
    <a class="btn secondary" href="{{ route('monitor.index') }}">Monitoreo</a>
    <a class="btn secondary" href="{{ route('alerts.index') }}">Centro NOC</a>
</div>

<div class="panel" style="overflow-x:auto">
    <h2>Historial de incidentes</h2>
    <div class="muted" style="margin-bottom:12px">
        El historial se consolida desde v0.8.0.
        @if($monitoringStartedAt)
            Inicio: {{ \Illuminate\Support\Carbon::parse($monitoringStartedAt)->format('Y-m-d H:i:s') }}.
        @endif
    </div>
    <table class="table">
        <thead><tr><th>Inicio</th><th>Fin</th><th>Duracion</th><th>Severidad</th><th>Estado</th></tr></thead>
        <tbody>
        @forelse($incidents as $incident)
            @php
                $end = $incident->ended_at ?: now();
                $seconds = $incident->duration_seconds;

                if ($seconds === null) {
                    $seconds = $incident->started_at
                        ? (int) round($incident->started_at->diffInSeconds($end))
                        : 0;
                }

                $incidentDuration = \Carbon\CarbonInterval::seconds((int) $seconds)
                    ->cascade()
                    ->forHumans(['short' => true, 'parts' => 3]);
            @endphp
            <tr>
                <td>{{ $incident->started_at?->format('Y-m-d H:i:s') ?? '—' }}</td>
                <td>{{ $incident->ended_at?->format('Y-m-d H:i:s') ?? 'En curso' }}</td>
                <td>{{ $incidentDuration }}</td>
                <td>{{ strtoupper($incident->severity) }}</td>
                <td>{{ $incident->ended_at ? 'RECUPERADO' : 'ABIERTO' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="muted">Aun no hay incidentes registrados para este canal.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
