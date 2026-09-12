
@extends('layout')



@section('heading','Monitoreo')

@section('subtitle','Estado en vivo de Astra, transporte y cobertura EPG')



@section('content')
<div class="actions" style="margin-bottom:14px">
<a class="btn" href="{{ route('alerts.index') }}">Centro NOC / Alertas</a>
</div>



<style>

.monitor-logo{

    width:38px;

    height:38px;

    flex:0 0 38px;

    border-radius:9px;

    background:#071827;

    border:1px solid rgba(56,189,248,.22);

    display:flex;

    align-items:center;

    justify-content:center;

    overflow:hidden

}

.monitor-logo img{

    width:100%;

    height:100%;

    object-fit:contain;

    padding:4px

}

.monitor-channel{

    display:flex;

    align-items:center;

    gap:9px

}

</style>





<section class="cards">



<div class="card">

    <div class="label">Astra</div>

    <div class="num" style="font-size:18px">

        {{ $server && !$server->last_error ? 'ONLINE' : 'ERROR' }}

    </div>

</div>



<div class="card">

    <div class="label">Canales ca&iacute;dos</div>

    <div class="num">{{ $offline->count() }}</div>

</div>



<div class="card">

    <div class="label">Canales activos</div>

    <div class="num">{{ $online->count() }}</div>

</div>



<div class="card">

    <div class="label">Con errores TS</div>

    <div class="num">{{ $transportErrors->count() }}</div>

</div>



<div class="card">

    <div class="label">&Uacute;ltimo Astra</div>

    <div class="num" style="font-size:15px">

        {{ $server?->last_seen_at?->diffForHumans() ?? '-' }}

    </div>

</div>



</section>





<section class="cards" style="margin-top:14px">



<div class="card">

    <div class="label">EPG real</div>

    <div class="num">

        {{ $guideStats['epg'] }}/{{ $guideStats['total'] }}

    </div>

</div>



<div class="card">

    <div class="label">Cobertura EPG</div>

    <div class="num">

        {{ number_format($coveragePct,1) }}%

    </div>

</div>



<div class="card">

    <div class="label">Radio</div>

    <div class="num">{{ $guideStats['radio'] }}</div>

</div>



<div class="card">

    <div class="label">Canales propios</div>

    <div class="num">{{ $guideStats['own'] }}</div>

</div>



<div class="card">

    <div class="label">Sin gu&iacute;a</div>

    <div class="num">{{ $guideStats['unavailable'] }}</div>

</div>



<div class="card">

    <div class="label">Prueba</div>

    <div class="num">{{ $guideStats['test'] }}</div>

</div>



</section>





<div class="panel">



<h2>Canales offline</h2>



<table class="table">



<thead>

<tr>

    <th>#</th>

    <th>Canal</th>

    <th>Desde</th>

    <th>Ca&iacute;das</th>

    <th>&Uacute;ltimo OK</th>

</tr>

</thead>



<tbody>



@forelse($offline as $c)



<tr>



<td>{{ $c->channel_number ?? '-' }}</td>



<td>

    <div class="monitor-channel">



        <div class="monitor-logo">

            @if($c->logo_url)

                <img src="{{ $c->logo_url }}" loading="lazy" alt="">

            @else

                <span class="muted">TV</span>

            @endif

        </div>



        <div>

            <strong>{{ $c->publicName() }}</strong>

            <div class="mono muted">{{ $c->astra_stream_id }}</div>

        </div>



    </div>

</td>



<td>{{ $c->offline_since?->diffForHumans() ?? '-' }}</td>

<td>{{ $c->outage_count }}</td>

<td>{{ $c->last_on_air_at?->diffForHumans() ?? '-' }}</td>



</tr>



@empty



<tr>

<td colspan="5">

    <span class="status on">

        <span class="s"></span>

        Todos los canales publicados est&aacute;n online.

    </span>

</td>

</tr>



@endforelse



</tbody>

</table>



</div>





<div class="grid2" style="margin-top:18px">



<section class="panel">



<h2>Mayor actividad</h2>



<table class="table">



<thead>

<tr>

    <th>Canal</th>

    <th>Sesiones</th>

    <th>Bitrate</th>

</tr>

</thead>



<tbody>



@foreach($online->take(20) as $c)



<tr>



<td>

    <div class="monitor-channel">



        <div class="monitor-logo">

            @if($c->logo_url)

                <img src="{{ $c->logo_url }}" loading="lazy" alt="">

            @else

                <span class="muted">TV</span>

            @endif

        </div>



        <div>{{ $c->publicName() }}</div>



    </div>

</td>



<td>{{ $c->sessions }}</td>



<td>

    {{ $c->bitrate ? number_format($c->bitrate/1000,2).' Mb/s' : '-' }}

</td>



</tr>



@endforeach



</tbody>

</table>



</section>





<section class="panel">



<h2>Errores de transporte</h2>



<table class="table">



<thead>

<tr>

    <th>Canal</th>

    <th>CC</th>

    <th>PES</th>

    <th>SCR</th>

</tr>

</thead>



<tbody>



@forelse($transportErrors as $c)



<tr>



<td>

    <div class="monitor-channel">



        <div class="monitor-logo">

            @if($c->logo_url)

                <img src="{{ $c->logo_url }}" loading="lazy" alt="">

            @else

                <span class="muted">TV</span>

            @endif

        </div>



        <div>{{ $c->publicName() }}</div>



    </div>

</td>



<td>{{ $c->cc_errors }}</td>

<td>{{ $c->pes_errors }}</td>

<td>{{ $c->scrambling_errors }}</td>



</tr>



@empty



<tr>

<td colspan="4" class="muted">

    Sin errores reportados.

</td>

</tr>



@endforelse



</tbody>

</table>



</section>



</div>



@endsection
