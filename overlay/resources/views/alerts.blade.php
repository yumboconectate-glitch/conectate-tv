@extends('layout')
@section('heading','Centro NOC')
@section('subtitle','Alertas automaticas de Astra, canales, transporte y EPG')
@section('content')

<style>
.noc-overall{display:flex;align-items:center;justify-content:space-between;gap:18px;padding:18px 20px;margin-bottom:18px;border-radius:16px;border:1px solid rgba(70,140,220,.28);background:linear-gradient(135deg,rgba(12,31,55,.96),rgba(8,23,43,.96))}
.noc-overall-left{display:flex;align-items:center;gap:14px}
.noc-orb{width:18px;height:18px;border-radius:50%;box-shadow:0 0 18px currentColor}
.noc-overall.normal{border-color:rgba(48,209,140,.45)}
.noc-overall.normal .noc-orb,.noc-overall.normal .noc-state{color:#30d18c}
.noc-overall.warning{border-color:rgba(255,193,7,.48)}
.noc-overall.warning .noc-orb,.noc-overall.warning .noc-state{color:#ffd166}
.noc-overall.critical{border-color:rgba(255,82,82,.52);box-shadow:0 0 28px rgba(255,82,82,.08)}
.noc-overall.critical .noc-orb,.noc-overall.critical .noc-state{color:#ff6262}
.noc-state{font-size:24px;font-weight:900;letter-spacing:.7px}
.noc-sub{font-size:12px;color:#8eb7e5;margin-top:4px}
.noc-live{font-size:12px;color:#79a7d8;text-align:right}
.noc-sev{display:inline-flex;align-items:center;gap:7px;font-weight:900;font-size:12px}
.noc-dot{width:8px;height:8px;border-radius:50%;display:inline-block}
.noc-sev.critical{color:#ff6262}.noc-sev.critical .noc-dot{background:#ff6262;box-shadow:0 0 10px #ff6262}
.noc-sev.warning{color:#ffd166}.noc-sev.warning .noc-dot{background:#ffd166;box-shadow:0 0 10px #ffd166}
.noc-sev.info{color:#58bfff}.noc-sev.info .noc-dot{background:#58bfff}
.noc-alert-title{display:flex;align-items:center;gap:10px}
.noc-logo{width:38px;height:38px;border-radius:9px;object-fit:contain;background:#071526;border:1px solid rgba(90,160,230,.28);padding:3px;flex:0 0 38px}
.noc-logo-fallback{width:38px;height:38px;border-radius:9px;display:grid;place-items:center;background:#071526;border:1px solid rgba(90,160,230,.28);font-size:11px;color:#8bb7e5;font-weight:800;flex:0 0 38px}
.noc-title-text{min-width:0}
.noc-title-text strong{display:block}
.noc-down{font-size:12px;font-weight:800;color:#ff9a9a;margin-top:5px}
.noc-actions{display:flex;gap:6px;flex-wrap:wrap}
.noc-mini{padding:6px 9px!important;font-size:11px!important}
.noc-table td{vertical-align:middle}
@media(max-width:980px){.noc-overall{align-items:flex-start;flex-direction:column}.noc-live{text-align:left}.noc-table{min-width:1050px}}
</style>

<div class="noc-overall {{ $overallState }}">
    <div class="noc-overall-left">
        <span class="noc-orb">●</span>
        <div>
            <div class="noc-state">ESTADO NOC: {{ $overallLabel }}</div>
            <div class="noc-sub">
                @if($overallState === 'critical')
                    Hay {{ $stats['critical'] }} alerta(s) critica(s) que requieren atencion.
                @elseif($overallState === 'warning')
                    No hay criticas, pero existen {{ $stats['warning'] }} advertencia(s).
                @else
                    No hay alertas activas. Plataforma operando normalmente.
                @endif
            </div>
        </div>
    </div>
    <div class="noc-live">
        <strong>Supervision automatica activa</strong><br>
        Astra · Canales · Transporte · EPG
    </div>
</div>

<section class="cards">
    <div class="card">
        <div class="label">ALERTAS ACTIVAS</div>
        <div class="num">{{ $stats['active'] }}</div>
    </div>
    <div class="card">
        <div class="label">CRITICAS</div>
        <div class="num" style="color:#ff6262">{{ $stats['critical'] }}</div>
    </div>
    <div class="card">
        <div class="label">ADVERTENCIAS</div>
        <div class="num" style="color:#ffd166">{{ $stats['warning'] }}</div>
    </div>
    <div class="card">
        <div class="label">RECONOCIDAS</div>
        <div class="num">{{ $stats['acknowledged'] }}</div>
    </div>
    <div class="card">
        <div class="label">RESUELTAS</div>
        <div class="num" style="color:#30d18c">{{ $stats['resolved'] }}</div>
    </div>
</section>

<div class="panel" style="margin-bottom:18px">
    <div class="actions" style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
        <form method="POST" action="{{ route('alerts.check') }}">
            @csrf
            <button class="btn" type="submit">Revisar ahora</button>
        </form>

        <a class="btn secondary" href="{{ route('alerts.index',['status'=>'active']) }}">Activas</a>
        <a class="btn secondary" href="{{ route('alerts.index',['status'=>'resolved']) }}">Resueltas</a>
        <a class="btn secondary" href="{{ route('alerts.index',['status'=>'all']) }}">Todas</a>
        <a class="btn secondary" href="{{ route('monitor.index') }}">Monitoreo</a>
    </div>
</div>

<div class="panel" style="overflow-x:auto">
    <h2>Eventos NOC</h2>

    <table class="table noc-table">
        <thead>
            <tr>
                <th>Severidad</th>
                <th>Estado</th>
                <th>Alerta</th>
                <th>Detalle</th>
                <th>Duracion</th>
                <th>Ultima vez</th>
                <th>Accion</th>
            </tr>
        </thead>
        <tbody>
        @forelse($alerts as $alert)
            @php
                $channel = $alert->entity_type === 'channel'
                    ? $channelMap->get((int) $alert->entity_id)
                    : null;

                $offlineSince = data_get($alert->context, 'offline_since');
                $durationText = $offlineSince
                    ? \Illuminate\Support\Carbon::parse($offlineSince)->diffForHumans(null, true)
                    : ($alert->first_seen_at?->diffForHumans(null, true) ?? '—');

                $severityClass = in_array($alert->severity, ['critical','warning'], true)
                    ? $alert->severity
                    : 'info';
            @endphp
            <tr>
                <td>
                    <span class="noc-sev {{ $severityClass }}">
                        <span class="noc-dot"></span>
                        {{ strtoupper($alert->severity) }}
                    </span>
                </td>
                <td>
                    @if($alert->status === 'active')
                        @if($alert->acknowledged_at)
                            <span class="status">RECONOCIDA</span>
                        @else
                            <span class="status on">ACTIVA</span>
                        @endif
                    @else
                        <span class="status" style="color:#30d18c">RESUELTA</span>
                    @endif
                </td>
                <td>
                    <div class="noc-alert-title">
                        @if($channel && $channel->logo_url)
                            <img class="noc-logo" src="{{ $channel->logo_url }}" alt="">
                        @elseif($channel)
                            <div class="noc-logo-fallback">TV</div>
                        @elseif($alert->type === 'transport_errors')
                            <div class="noc-logo-fallback">TS</div>
                        @elseif(str_starts_with($alert->type, 'epg_'))
                            <div class="noc-logo-fallback">EPG</div>
                        @else
                            <div class="noc-logo-fallback">NOC</div>
                        @endif

                        <div class="noc-title-text">
                            <strong>{{ $alert->title }}</strong>
                            <div class="mono muted">
                                {{ $alert->type }} · {{ $alert->fingerprint }}
                            </div>
                            @if($channel && $channel->astra_stream_id)
                                <div class="mono muted">
                                    Stream {{ $channel->astra_stream_id }}
                                </div>
                            @endif
                        </div>
                    </div>
                </td>
                <td style="max-width:420px">
                    {{ $alert->message ?: '—' }}
                    @if($channel && !$channel->on_air)
                        <div class="noc-down">
                            ⏱ Fuera del aire: {{ $durationText }}
                        </div>
                    @endif
                </td>
                <td>
                    <strong>{{ $durationText }}</strong>
                    <div class="muted">
                        desde {{ $offlineSince ? \Illuminate\Support\Carbon::parse($offlineSince)->diffForHumans() : ($alert->first_seen_at?->diffForHumans() ?? '—') }}
                    </div>
                </td>
                <td>
                    {{ $alert->last_seen_at?->diffForHumans() ?? '—' }}
                </td>
                <td>
                    <div class="noc-actions">
                        @if($alert->status === 'active' && !$alert->acknowledged_at)
                            <form method="POST" action="{{ route('alerts.ack',$alert) }}">
                                @csrf
                                <button class="btn secondary noc-mini" type="submit">Reconocer</button>
                            </form>
                        @endif

                        <a class="btn secondary noc-mini" href="{{ route('monitor.index') }}">Monitoreo</a>

                        @if($channel && \Illuminate\Support\Facades\Route::has('channels.index'))
                            <a class="btn secondary noc-mini" href="{{ route('channels.index') }}">Canales</a>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" class="muted">
                    No hay alertas para este filtro.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
