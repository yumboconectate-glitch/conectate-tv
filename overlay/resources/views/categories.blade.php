@extends('layout')
@section('heading', 'Categorías')
@section('subtitle', 'Organiza la parrilla que ven las TVs y apps Xtream')
@section('content')
<div class="grid2">
<section class="panel">
<h2>Nueva categoría</h2>
<form method="POST" action="{{ route('categories.store') }}">
@csrf
<div class="field"><label>Nombre</label><input name="name" placeholder="Ej. Deportes" required></div>
<div class="field"><label>Orden</label><input type="number" name="sort_order" value="100" min="0" max="9999" required></div>
<button class="btn" type="submit">Crear categoría</button>
</form>
</section>
<section class="panel">
<h2>Categorías actuales</h2>
@foreach($categories as $category)
<form method="POST" action="{{ route('categories.update', $category) }}" style="border-top:1px solid #18385d;padding:12px 0">
@csrf @method('PUT')
<div style="display:grid;grid-template-columns:2fr 100px auto auto;gap:8px;align-items:end">
<div class="field" style="margin:0"><label>Nombre</label><input name="name" value="{{ $category->name }}" required></div>
<div class="field" style="margin:0"><label>Orden</label><input type="number" name="sort_order" value="{{ $category->sort_order }}" min="0" max="9999"></div>
<div class="check"><input type="hidden" name="active" value="0"><input id="cat-{{ $category->id }}" type="checkbox" name="active" value="1" {{ $category->active ? 'checked' : '' }}><label for="cat-{{ $category->id }}">Activa</label></div>
<button class="btn secondary" type="submit">Guardar</button>
</div>
<div class="muted" style="margin-top:6px">{{ $category->channels_count }} canales</div>
</form>
@endforeach
</section>
</div>
@endsection
