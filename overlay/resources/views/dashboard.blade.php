
@extends('layout')



@section('heading','Conectate TV')

@section('subtitle','Operacion IPTV - EPG - monitoreo - clientes - Astra')



@section('content')



<style>



.astra-hero{

    margin-bottom:18px;

    padding:20px;

    border-radius:18px;

    border:1px solid rgba(14,165,233,.28);

    background:

        radial-gradient(

            circle at 85% 10%,

            rgba(14,165,233,.16),

            transparent 34%

        ),

        linear-gradient(

            135deg,

            rgba(5,22,40,.98),

            rgba(7,35,61,.96)

        );

    box-shadow:0 15px 40px rgba(0,0,0,.18);

}



.astra-head{

    display:flex;

    align-items:center;

    justify-content:space-between;

    gap:20px;

    margin-bottom:18px;

}



.astra-title{

    display:flex;

    align-items:center;

    gap:12px;

}



.astra-dot{

    width:12px;

    height:12px;

    border-radius:50%;

    background:#22c55e;

    box-shadow:0 0 14px rgba(34,197,94,.75);

}



.astra-dot.off{

    background:#ef4444;

    box-shadow:0 0 14px rgba(239,68,68,.75);

}



.astra-name{

    font-size:19px;

    font-weight:800;

}



.astra-host{

    margin-top:3px;

    color:#7dd3fc;

    font-family:monospace;

    font-size:12px;

}



.astra-grid{

    display:grid;

    grid-template-columns:repeat(8,minmax(100px,1fr));

    gap:10px;

}



.astra-metric{

    padding:13px;

    border-radius:13px;

    background:rgba(2,15,28,.55);

    border:1px solid rgba(56,189,248,.16);

}



.astra-metric .m-label{

    color:#8eb8d9;

    font-size:11px;

    margin-bottom:5px;

}



.astra-metric .m-value{

    color:white;

    font-size:20px;

    font-weight:800;

}



.astra-metric .m-sub{

    color:#7dd3fc;

    font-size:11px;

    margin-top:3px;

}



.channel-cell{

    display:flex;

    align-items:center;

    gap:9px;

}



.channel-thumb{

    width:42px;

    height:42px;

    flex:0 0 42px;

    border-radius:9px;

    background:#071827;

    border:1px solid rgba(56,189,248,.22);

    overflow:hidden;

    display:flex;

    align-items:center;

    justify-content:center;

}



.channel-thumb img{

    width:100%;

    height:100%;

    object-fit:contain;

    padding:4px;

}



@media(max-width:1250px){

    .astra-grid{

        grid-template-columns:repeat(4,1fr);

    }

}



</style>





<section class="astra-hero">



    <div class="astra-head">



        <div class="astra-title">



            <span class="astra-dot {{ $astraMetrics['online'] ? '' : 'off' }}"></span>



            <div>

                <div class="astra-name">

                    {{ $server?->name ?? 'Astra' }}

                    -

                    {{ $astraMetrics['online'] ? 'ONLINE' : 'ERROR' }}

                </div>



                <div class="astra-host">

                    {{ $server?->host ?? '-' }}:{{ $server?->port ?? '-' }}

                    &nbsp;&middot;&nbsp;

                    ultimo sync:

                    {{ $server?->last_seen_at?->diffForHumans() ?? '-' }}

                </div>

            </div>



        </div>



        <div class="status {{ $astraMetrics['online'] ? 'on' : 'off' }}">

            <span class="s"></span>

            ASTRA

        </div>



    </div>





    <div class="astra-grid">



        <div class="astra-metric">

            <div class="m-label">CPU ASTRA</div>

            <div class="m-value">

                {{ $astraMetrics['app_cpu'] }}%

            </div>

            <div class="m-sub">

                proceso Astra

            </div>

        </div>





        <div class="astra-metric">

            <div class="m-label">CPU SERVIDOR</div>

            <div class="m-value">

                {{ $astraMetrics['sys_cpu'] }}%

            </div>

            <div class="m-sub">

                uso sistema

            </div>

        </div>





        <div class="astra-metric">

            <div class="m-label">RAM ASTRA</div>

            <div class="m-value">

                {{ number_format($astraMetrics['app_mem_gb'],2) }} GB

            </div>

            <div class="m-sub">

                {{ $astraMetrics['app_mem_percent'] }}% reportado

            </div>

        </div>





        <div class="astra-metric">

            <div class="m-label">RAM SERVIDOR</div>

            <div class="m-value">

                {{ $astraMetrics['sys_mem_percent'] }}%

            </div>

            <div class="m-sub">

                memoria usada

            </div>

        </div>





        <div class="astra-metric">

            <div class="m-label">UPTIME ASTRA</div>

            <div class="m-value" style="font-size:16px">

                {{ $astraMetrics['app_uptime'] }}

            </div>

            <div class="m-sub">

                aplicacion

            </div>

        </div>





        <div class="astra-metric">

            <div class="m-label">UPTIME SERVIDOR</div>

            <div class="m-value" style="font-size:16px">

                {{ $astraMetrics['sys_uptime'] }}

            </div>

            <div class="m-sub">

                sistema

            </div>

        </div>





        <div class="astra-metric">

            <div class="m-label">LOAD 1 / 5 / 15</div>

            <div class="m-value" style="font-size:15px">

                {{ number_format($astraMetrics['la1'],2) }}

                /

                {{ number_format($astraMetrics['la5'],2) }}

                /

                {{ number_format($astraMetrics['la15'],2) }}

            </div>

            <div class="m-sub">

                carga promedio

            </div>

        </div>





        <div class="astra-metric">

            <div class="m-label">THREADS</div>

            <div class="m-value">

                {{ $astraMetrics['threads'] }}

            </div>

            <div class="m-sub">

                Astra

            </div>

        </div>



    </div>



</section>





<section class="cards">



    <div class="card">

        <div class="label">Canales publicados</div>

        <div class="num cyan">

            {{ $stats['published'] }}

        </div>

    </div>



    <div class="card">

        <div class="label">ON AIR</div>

        <div class="num">

            {{ $stats['onair'] }}

        </div>

    </div>



    <div class="card">

        <div class="label">OFFLINE</div>

        <div class="num">

            {{ $stats['offline'] }}

        </div>

        <div class="muted">

            {{ $stats['offline5'] }} &gt; 5 min

        </div>

    </div>



    <div class="card">

        <div class="label">Sesiones</div>

        <div class="num">

            {{ $stats['sessions'] }}

        </div>

    </div>



    <div class="card">

        <div class="label">Bitrate total</div>

        <div class="num">

            {{

                $stats['total_bitrate']

                    ? number_format($stats['total_bitrate']/1000,1)

                    : '0'

            }}

        </div>

        <div class="muted">Mb/s</div>

    </div>



    <div class="card">

        <div class="label">EPG mapeado</div>

        <div class="num">

            {{ $stats['epg_mapped'] }}/{{ $stats['published'] }}

        </div>

    </div>



</section>





<div class="grid">



<section class="panel">



<h2>Estado de canales</h2>



<table class="table">



<thead>

<tr>

    <th>#</th>

    <th>Canal</th>

    <th>Categoria</th>

    <th>Estado</th>

    <th>Bitrate</th>

    <th>EPG</th>

</tr>

</thead>



<tbody>



@foreach($channels->where('published',true)->take(35) as $channel)



<tr>



<td>{{ $channel->channel_number ?? '-' }}</td>



<td>



    <div class="channel-cell">



        <div class="channel-thumb">



            @if($channel->logo_url)



                <img

                    src="{{ $channel->logo_url }}"

                    loading="lazy"

                    alt="{{ $channel->publicName() }}"

                >



            @else



                <span class="muted">TV</span>



            @endif



        </div>



        <div>

            <strong>

                {{ $channel->publicName() }}

            </strong>



            <div class="mono muted">

                {{ $channel->astra_stream_id }}

            </div>

        </div>



    </div>



</td>



<td>

    {{ $channel->category?->name ?? 'General' }}

</td>



<td>



    <span class="status {{ $channel->on_air ? 'on' : 'off' }}">

        <span class="s"></span>

        {{ $channel->on_air ? 'ON AIR' : 'OFFLINE' }}

    </span>



    @if(!$channel->on_air && $channel->offline_since)

        <div class="muted">

            {{ $channel->offline_since->diffForHumans() }}

        </div>

    @endif



</td>



<td>

    {{

        $channel->bitrate

            ? number_format($channel->bitrate/1000,2).' Mb/s'

            : '-'

    }}

</td>



<td>

    {{ $channel->epg_xmltv_id ? 'OK' : '-' }}

</td>



</tr>



@endforeach



</tbody>



</table>



</section>





<aside class="panel">



<h2>Operacion</h2>



<div class="card">

    <div class="label">Clientes activos</div>

    <div class="num">

        {{ $stats['active_clients'] }}

    </div>

</div>



<div class="card" style="margin-top:10px">

    <div class="label">Vencen 7 dias</div>

    <div class="num">

        {{ $stats['expiring7'] }}

    </div>

</div>



<div class="card" style="margin-top:10px">

    <div class="label">Fuentes EPG</div>

    <div class="num">

        {{ $stats['epg_sources'] }}

    </div>

</div>





<h2 style="margin-top:20px">

    Actividad reciente

</h2>



@forelse($recentActivity as $log)



<div style="padding:9px 0;border-top:1px solid #18385d">



    <div class="mono cyan">

        {{ $log->action }}

    </div>



    <div>

        {{

            $log->subscriber?->name

            ?? ($log->meta['name'] ?? 'Sistema')

        }}

    </div>



    <div class="muted">

        {{ $log->created_at?->diffForHumans() }}

    </div>



</div>



@empty



<div class="muted">

    Sin actividad aun.

</div>



@endforelse



</aside>



</div>



@endsection
