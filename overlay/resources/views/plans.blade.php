@extends('layout')
@section('heading', 'Planes')
@section('subtitle', 'Crea paquetes y decide qué canales recibe cada abonado')
@section('content')
<div class="grid2">
<section class="panel">
<h2>Nuevo plan</h2>
<form method="POST" action="{{ route('plans.store') }}">
@csrf
<div class="field"><label>Nombre</label><input name="name" placeholder="Ej. Básico+" required></div>
<div class="field"><label>Descripción</label><textarea name="description" placeholder="Descripción comercial"></textarea></div>
<div class="field"><label>Conexiones máximas</label><input type="number" name="max_connections" min="1" max="20" value="2" required></div>
<div class="field"><label>Canales incluidos</label>
  <div class="checks">
  @foreach($channels as $channel)
    <div class="check"><input id="new-{{ $channel->id }}" type="checkbox" name="channels[]" value="{{ $channel->id }}" checked><label for="new-{{ $channel->id }}">{{ $channel->name }}</label></div>
  @endforeach
  </div>
</div>
<button class="btn" type="submit">Crear plan</button>
</form>
</section>

<section class="panel">
<h2>Planes existentes</h2>
@forelse($plans as $plan)
<form method="POST" action="{{ route('plans.update', $plan) }}" style="border-top:1px solid #18385d;padding:14px 0">
@csrf @method('PUT')
<div class="field"><label>Nombre</label><input name="name" value="{{ $plan->name }}" required></div>
<div class="field"><label>Descripción</label><textarea name="description">{{ $plan->description }}</textarea></div>
<div class="field"><label>Conexiones</label><input type="number" name="max_connections" min="1" max="20" value="{{ $plan->max_connections }}"></div>
<input type="hidden" name="active" value="0">
<div class="check" style="margin-bottom:10px"><input id="active-{{ $plan->id }}" type="checkbox" name="active" value="1" {{ $plan->active ? 'checked' : '' }}><label for="active-{{ $plan->id }}">Plan activo</label></div>
<div class="checks">
@foreach($channels as $channel)
<div class="check"><input id="p{{ $plan->id }}c{{ $channel->id }}" type="checkbox" name="channels[]" value="{{ $channel->id }}" {{ $plan->channels->contains($channel->id) ? 'checked' : '' }}><label for="p{{ $plan->id }}c{{ $channel->id }}">{{ $channel->name }}</label></div>
@endforeach
</div>
<div class="muted" style="margin:10px 0">{{ $plan->channels_count }} canales · {{ $plan->subscribers_count }} clientes</div>
<button class="btn secondary" type="submit">Guardar cambios</button>
</form>
@empty
<p class="muted">Aún no hay planes. Crea el primero.</p>
@endforelse
</section>
</div>
@endsection
