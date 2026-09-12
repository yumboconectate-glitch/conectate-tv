@extends('layout')
@section('heading','Operacion IPTV')
@section('subtitle','Disponibilidad, incidentes, errores TS y salud operativa de la parrilla')
@section('content')

<style>
.op-kpi{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px;margin-bottom:18px}
.op-filter{display:flex;gap:8px;align-items:end;flex-wrap:wrap}
.op-filter .field{margin:0;min-width:180px}
.op-logo{width:42px;height:42px;object-fit:contain;border-radius:9px;background:#071526;border:1px solid rgba(90,160,230,.28);padding:3px}
.op-channel{display:flex;align-items:center;gap:10px}
.op-up{font-weight:900}
.op-up.good{color:#30d18c}.op-up.warn{color:#ffd166}.op-up.bad{color:#ff6262}
.op-errors{font-family:monospace;font-size:12px}
.op-note{padding:10px 12px;border:1px solid rgba(70,140,220,.24);border-radius:10px;color:#83add9;font-size:12px;margin-bottom:18px}
@media(max-width:1100px){.op-kpi{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>

<div class="op-note">
    La disponibilidad empieza a consolidarse desde la instalacion de v0.8.0.
    Mientras completa 24h, 7d y 30d, el porcentaje se calcula sobre el periodo realmente observado.
    @if($monitoringStartedAt)
        Inicio del historial: {{ \Illuminate\Support\Carbon::parse($monitoringStartedAt)->format('Y-m-d H:i:s') }}.
    @endif
</div>

<section class="cards">
    <div class="card"><div class="label">CANALES</div><div class="num">{{ $stats['total'] }}</div></div>
    <div class="card"><div class="label">ON AIR</div><div class="num" style="color:#30d18c">{{ $stats['online'] }}</div></div>
    <div class="card"><div class="label">OFFLINE</div><div class="num" style="color:#ff6262">{{ $stats['offline'] }}</div></div>
    <div class="card"><div class="label">CON ERRORES TS</div><div class="num" style="color:#ffd166">{{ $stats['errors'] }}</div></div>
    <div class="card"><div class="label">DISPONIBILIDAD 24H</div><div class="num">{{ number_format($stats['avg_uptime_24h'],3) }}%</div></div>
</section>

<div class="panel" style="margin-bottom:18px">
    <form method="GET" action="{{ route('operations.index') }}" class="op-filter">
        <div class="field">
            <label>Buscar canal</label>
            <input name="q" value="{{ $search }}" placeholder="Nombre, numero o stream">
        </div>
        <div class="field">
            <label>Estado</label>
            <select name="status">
                <option value="all" {{ $status==='all'?'selected':'' }}>Todos</option>
                <option value="online" {{ $status==='online'?'selected':'' }}>Online</option>
                <option value="offline" {{ $status==='offline'?'selected':'' }}>Offline</option>
                <option value="errors" {{ $status==='errors'?'selected':'' }}>Errores TS</option>
            </select>
        </div>
        <button class="btn" type="submit">Filtrar</button>
        <a class="btn secondary" href="{{ route('operations.index') }}">Limpiar</a>
        <a class="btn secondary" href="{{ route('monitor.index') }}">Monitoreo vivo</a>
        <a class="btn secondary" href="{{ route('alerts.index') }}">Centro NOC</a>
    </form>
</div>

<div class="grid2" style="margin-bottom:18px">
    <section class="panel">
        <h2>Canales mas problematicos - 7 dias</h2>
        <table class="table">
            <thead><tr><th>Canal</th><th>Incidentes</th><th>Caida</th><th>Uptime</th></tr></thead>
            <tbody>
            @foreach($problematic as $channel)
                @php
                    $m = $metrics[$channel->id] ?? null;
                    $problemDowntimeSeconds = (int) data_get($m, 'downtime_7d', 0);
                    $problemDowntime = \Carbon\CarbonInterval::seconds($problemDowntimeSeconds)
                        ->cascade()
                        ->forHumans(['short' => true, 'parts' => 2]);
                @endphp
                <tr>
                    <td>
                        <a href="{{ route('operations.channel',$channel) }}">{{ $channel->publicName() }}</a>
                    </td>
                    <td>{{ data_get($m,'incidents_7d',0) }}</td>
                    <td>{{ $problemDowntime }}</td>
                    <td>{{ number_format((float)data_get($m,'uptime_7d.percent',100),3) }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </section>

    <section class="panel">
        <h2>Lectura rapida</h2>
        <div class="muted" style="line-height:1.8">
            <strong style="color:#30d18c">Verde:</strong> disponibilidad mayor o igual a 99.5%.<br>
            <strong style="color:#ffd166">Amarillo:</strong> entre 98% y 99.5%.<br>
            <strong style="color:#ff6262">Rojo:</strong> menor de 98%.<br><br>
            Los errores CC, PES y SCR son los valores actuales informados por la sincronizacion con Astra.
        </div>
    </section>
</div>

<div class="panel" style="overflow-x:auto">
    <h2>Parrilla operativa</h2>
    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Canal</th>
                <th>Estado</th>
                <th>Bitrate</th>
                <th>Sesiones</th>
                <th>CC / PES / SCR</th>
                <th>24h</th>
                <th>7d</th>
                <th>30d</th>
                <th>Detalle</th>
            </tr>
        </thead>
        <tbody>
        @foreach($channels as $channel)
            @php
                $m = $metrics[$channel->id] ?? [];
                $u24 = (float) data_get($m, 'uptime_24h.percent', 100);
                $u7 = (float) data_get($m, 'uptime_7d.percent', 100);
                $u30 = (float) data_get($m, 'uptime_30d.percent', 100);

                if ($u24 >= 99.5) {
                    $class24 = 'good';
                } elseif ($u24 >= 98) {
                    $class24 = 'warn';
                } else {
                    $class24 = 'bad';
                }
            @endphp
            <tr>
                <td>{{ $channel->channel_number ?? '—' }}</td>
                <td>
                    <div class="op-channel">
                        @if($channel->logo_url)
                            <img class="op-logo" src="{{ $channel->logo_url }}" alt="">
                        @endif
                        <div>
                            <strong>{{ $channel->publicName() }}</strong>
                            <div class="mono muted">{{ $channel->astra_stream_id }}</div>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="status {{ $channel->on_air ? 'on' : 'off' }}">
                        {{ $channel->on_air ? 'ON AIR' : 'OFFLINE' }}
                    </span>
                    @if(!$channel->on_air && $channel->offline_since)
                        <div class="muted">{{ $channel->offline_since->diffForHumans() }}</div>
                    @endif
                </td>
                <td>{{ $channel->bitrate ? number_format($channel->bitrate/1000,2).' Mb/s' : '—' }}</td>
                <td>{{ (int)$channel->sessions }}</td>
                <td class="op-errors">
                    {{ (int)$channel->cc_errors }} /
                    {{ (int)$channel->pes_errors }} /
                    {{ (int)$channel->scrambling_errors }}
                </td>
                <td><span class="op-up {{ $class24 }}">{{ number_format($u24,3) }}%</span></td>
                <td>{{ number_format($u7,3) }}%</td>
                <td>{{ number_format($u30,3) }}%</td>
                <td><a class="btn secondary" href="{{ route('operations.channel',$channel) }}">Ver canal</a></td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>

@endsection
