@extends('layout')
@section('heading', 'Canales')
@section('subtitle', 'Catálogo comercial, numeración, categorías y logos')
@section('content')
<div class="panel">
<table class="table">
<thead><tr><th>#</th><th>Canal</th><th>Categoría</th><th>Logo</th><th>Estado</th><th>Bitrate</th><th>Publicar</th><th>Guardar</th></tr></thead>
<tbody>
@foreach($channels as $channel)
<tr>
<form method="POST" action="{{ route('channels.update', $channel) }}">
@csrf @method('PUT')
<td><input type="number" name="channel_number" value="{{ $channel->channel_number }}" min="1" max="99999" style="width:75px"></td>
<td>
  <strong>{{ $channel->name }}</strong>
  <div class="mono muted">{{ $channel->astra_stream_id ?: 'sin-id' }}</div>
  <input name="display_name" value="{{ $channel->display_name }}" placeholder="Nombre comercial" style="margin-top:7px;min-width:190px">
</td>
<td>
<select name="category_id" style="min-width:150px">
<option value="">Sin categoría</option>
@foreach($categories as $category)
<option value="{{ $category->id }}" {{ $channel->category_id === $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
@endforeach
</select>
</td>
<td>
<input name="logo_url" value="{{ $channel->logo_url }}" placeholder="https://.../logo.png" style="min-width:210px">
@if($channel->logo_url)<div style="margin-top:6px"><img src="{{ $channel->logo_url }}" alt="" style="width:42px;height:42px;object-fit:contain;border-radius:8px;background:#fff"></div>@endif
</td>
<td><span class="status {{ $channel->on_air ? 'on' : 'off' }}"><span class="s"></span>{{ $channel->on_air ? 'ON AIR' : 'OFFLINE' }}</span></td>
<td>{{ $channel->bitrate ? number_format($channel->bitrate / 1000, 2).' Mb/s' : '—' }}</td>
<td>
<input type="hidden" name="published" value="0">
<input type="checkbox" name="published" value="1" {{ $channel->published ? 'checked' : '' }} style="width:auto">
</td>
<td><button class="btn secondary" type="submit">Guardar</button></td>
</form>
</tr>
@endforeach
</tbody>
</table>
</div>
@endsection
