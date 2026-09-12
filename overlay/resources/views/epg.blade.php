
@extends('layout')



@section('heading','EPG')

@section('subtitle','Gu&iacute;a electr&oacute;nica XMLTV, mapeo de canales y programaci&oacute;n')



@section('content')



<style>

.epg-summary{

    display:grid;

    grid-template-columns:repeat(6,minmax(120px,1fr));

    gap:10px;

    margin-bottom:18px

}

.epg-summary .card{min-height:92px}

.epg-summary .num{font-size:25px}

.epg-channel{

    display:flex;

    align-items:center;

    gap:12px;

    min-width:250px

}

.epg-logo{

    width:54px;

    height:54px;

    flex:0 0 54px;

    border-radius:12px;

    border:1px solid rgba(56,189,248,.25);

    background:#071827;

    display:flex;

    align-items:center;

    justify-content:center;

    overflow:hidden;

    box-shadow:0 5px 16px rgba(0,0,0,.18)

}

.epg-logo img{

    width:100%;

    height:100%;

    object-fit:contain;

    padding:5px

}

.epg-logo-fallback{

    width:100%;

    height:100%;

    display:flex;

    align-items:center;

    justify-content:center;

    font-weight:800;

    color:#38bdf8

}

.epg-badge{

    display:inline-flex;

    align-items:center;

    padding:5px 9px;

    border-radius:999px;

    font-size:11px;

    font-weight:800;

    letter-spacing:.3px;

    white-space:nowrap

}

.epg-badge.epg{background:rgba(16,185,129,.15);color:#6ee7b7;border:1px solid rgba(16,185,129,.35)}

.epg-badge.radio{background:rgba(59,130,246,.15);color:#93c5fd;border:1px solid rgba(59,130,246,.35)}

.epg-badge.own{background:rgba(168,85,247,.15);color:#d8b4fe;border:1px solid rgba(168,85,247,.35)}

.epg-badge.test{background:rgba(245,158,11,.15);color:#fcd34d;border:1px solid rgba(245,158,11,.35)}

.epg-badge.unavailable{background:rgba(148,163,184,.12);color:#cbd5e1;border:1px solid rgba(148,163,184,.25)}

.epg-kind-select{margin-top:7px;min-width:145px}

@media(max-width:1100px){

    .epg-summary{grid-template-columns:repeat(3,1fr)}

}

</style>



<section class="epg-summary">

    <div class="card">

        <div class="label">EPG real</div>

        <div class="num">{{ $guideStats['epg'] }}</div>

        <div class="muted">de {{ $guideStats['total'] }} canales</div>

    </div>



    <div class="card">

        <div class="label">Cobertura real</div>

        <div class="num">{{ number_format($coveragePct,1) }}%</div>

        <div class="muted">con programas futuros</div>

    </div>



    <div class="card">

        <div class="label">Radio</div>

        <div class="num">{{ $guideStats['radio'] }}</div>

        <div class="muted">sin parrilla TV</div>

    </div>



    <div class="card">

        <div class="label">Canal propio</div>

        <div class="num">{{ $guideStats['own'] }}</div>

        <div class="muted">contenido continuo</div>

    </div>



    <div class="card">

        <div class="label">Prueba</div>

        <div class="num">{{ $guideStats['test'] }}</div>

        <div class="muted">se&ntilde;ales de test</div>

    </div>



    <div class="card">

        <div class="label">Sin gu&iacute;a</div>

        <div class="num">{{ $guideStats['unavailable'] }}</div>

        <div class="muted">sin EPG real</div>

    </div>

</section>





<div class="grid2">



<section class="panel">

    <h2>Nueva fuente XMLTV</h2>



    <form method="POST" action="{{ route('epg.sources.store') }}">

        @csrf



        <div class="field">

            <label>Nombre</label>

            <input name="name" placeholder="EPG Principal" required>

        </div>



        <div class="field">

            <label>URL XMLTV</label>

            <input name="url" placeholder="https://.../guide.xml" required>

        </div>



        <div class="field">

            <label>Actualizar cada (horas)</label>

            <input type="number" name="refresh_hours" value="6" min="1" max="72" required>

        </div>



        <button class="btn" type="submit">Agregar fuente</button>

    </form>

</section>





<section class="panel">

    <h2>Fuentes</h2>



    @forelse($sources as $source)

        <div class="card" style="margin-bottom:10px">



            <form method="POST" action="{{ route('epg.sources.update',$source) }}">

                @csrf

                @method('PUT')



                <div style="display:grid;grid-template-columns:1fr 90px auto;gap:8px">

                    <input name="name" value="{{ $source->name }}">



                    <input

                        type="number"

                        name="refresh_hours"

                        value="{{ $source->refresh_hours }}"

                        min="1"

                        max="72"

                    >



                    <label class="check" style="margin:0">

                        <input type="hidden" name="enabled" value="0">

                        <input

                            type="checkbox"

                            name="enabled"

                            value="1"

                            {{ $source->enabled ? 'checked' : '' }}

                        >

                        Activa

                    </label>

                </div>



                <input

                    name="url"

                    value="{{ $source->url }}"

                    style="margin-top:8px"

                >



                <div class="actions" style="margin-top:8px">

                    <button class="btn secondary" type="submit">Guardar</button>

            </form>



            <form method="POST" action="{{ route('epg.sources.import',$source) }}">

                @csrf

                <button class="btn" type="submit">Importar ahora</button>

            </form>

                </div>



            <div class="muted" style="margin-top:8px">

                {{ $source->epg_channels_count }} canales &middot;

                {{ $source->last_programme_count }} programas &middot;

                &uacute;ltima:

                {{ $source->last_success_at?->diffForHumans() ?? 'nunca' }}

            </div>



            @if($source->last_error)

                <div class="flash warn" style="margin-top:8px">

                    {{ $source->last_error }}

                </div>

            @endif



        </div>

    @empty

        <div class="muted">

            A&uacute;n no tienes fuente EPG.

        </div>

    @endforelse

</section>



</div>





<div class="panel" style="margin-top:18px">



<h2>Mapeo de la parrilla</h2>



<table class="table">



<thead>

<tr>

    <th>#</th>

    <th>Canal</th>

    <th>Estado</th>

    <th>EPG asignado</th>

    <th>Ahora</th>

    <th>Guardar</th>

</tr>

</thead>



<tbody>



@foreach($channels as $channel)



@php

    $formId = 'epg-map-'.$channel->id;

    $state = $guideStates[$channel->id] ?? 'unavailable';



    $labels = [

        'epg' => 'EPG REAL',

        'radio' => 'RADIO',

        'own' => 'CANAL PROPIO',

        'test' => 'PRUEBA',

        'unavailable' => 'SIN GUIA',

    ];



    $p = $channel->epg_source_id && $channel->epg_xmltv_id

        ? $current->get($channel->epg_source_id.'|'.$channel->epg_xmltv_id)

        : null;

@endphp



<tr>



<td>

    <form id="{{ $formId }}" method="POST" action="{{ route('epg.map',$channel) }}">

        @csrf

        @method('PUT')

    </form>



    {{ $channel->channel_number ?? '-' }}

</td>



<td>

    <div class="epg-channel">



        <div class="epg-logo">



            @if($channel->logo_url)



                <img

                    src="{{ $channel->logo_url }}"

                    alt="{{ $channel->publicName() }}"

                    loading="lazy"

                    onerror="this.style.display='none';this.nextElementSibling.style.display='flex'"

                >



                <span class="epg-logo-fallback" style="display:none">TV</span>



            @else



                <span class="epg-logo-fallback">TV</span>



            @endif



        </div>



        <div>

            <strong>{{ $channel->publicName() }}</strong>



            <div class="muted" style="margin-top:3px">

                Canal {{ $channel->channel_number ?? '-' }}

            </div>



            <div class="mono muted" style="font-size:11px">

                {{ $channel->astra_stream_id }}

            </div>

        </div>



    </div>

</td>





<td>

    <span class="epg-badge {{ $state }}">

        {{ $labels[$state] ?? 'SIN GUIA' }}

    </span>



    <div>

        <select

            class="epg-kind-select"

            name="epg_kind"

            form="{{ $formId }}"

        >

            <option value="" {{ !$channel->epg_kind ? 'selected' : '' }}>

                Automatico

            </option>



            <option value="radio" {{ $channel->epg_kind === 'radio' ? 'selected' : '' }}>

                Radio

            </option>



            <option value="own" {{ $channel->epg_kind === 'own' ? 'selected' : '' }}>

                Canal propio

            </option>



            <option value="test" {{ $channel->epg_kind === 'test' ? 'selected' : '' }}>

                Prueba

            </option>

        </select>

    </div>

</td>





<td>

    <select

        name="epg_channel_id"

        form="{{ $formId }}"

        style="min-width:300px"

    >



        <option value="">Sin EPG</option>



        @foreach($epgChannels as $ec)



            <option

                value="{{ $ec->id }}"

                {{

                    $channel->epg_source_id == $ec->epg_source_id &&

                    $channel->epg_xmltv_id == $ec->xmltv_id

                    ? 'selected'

                    : ''

                }}

            >

                {{ $ec->source?->name }}

                - {{ $ec->display_name }}

                [{{ $ec->xmltv_id }}]

            </option>



        @endforeach



    </select>



    @if($channel->epg_auto_mapped_at)

        <div class="muted">Auto-mapeado</div>

    @endif

</td>





<td>



    @if($p)



        <strong>{{ $p->title }}</strong>



        <div class="muted">

            hasta {{ $p->stop_at->format('H:i') }}

        </div>



    @elseif($state === 'epg')



        <span class="muted">Sin programa ahora</span>



    @elseif($state === 'radio')



        <span class="muted">Se&ntilde;al de radio</span>



    @elseif($state === 'own')



        <span class="muted">Contenido continuo</span>



    @elseif($state === 'test')



        <span class="muted">Canal de prueba</span>



    @else



        <span class="muted">Sin gu&iacute;a disponible</span>



    @endif



</td>





<td>

    <button

        class="btn secondary"

        type="submit"

        form="{{ $formId }}"

    >

        Guardar

    </button>

</td>



</tr>



@endforeach



</tbody>

</table>



</div>



@endsection
