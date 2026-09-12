@extends('layout')
@section('heading','Configuracion')
@section('subtitle','Correo, Telegram, usuarios e integraciones API')
@section('content')

<style>
.cfg-tabs{display:flex;gap:7px;flex-wrap:wrap;margin-bottom:18px}
.cfg-tab{display:inline-flex;padding:9px 12px;border-radius:9px;border:1px solid rgba(70,140,220,.32);text-decoration:none;color:#a9cbed;background:#0b2038}
.cfg-tab.active{background:#0b4d79;color:#fff;border-color:#1db3ff}
.cfg-secret{font-size:12px;color:#30d18c;margin-top:5px}
.cfg-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}
.cfg-user{display:grid;grid-template-columns:1.2fr 1.4fr .8fr .8fr 1fr auto;gap:8px;align-items:end;padding:12px 0;border-bottom:1px solid rgba(90,160,230,.16)}
.cfg-help{font-size:12px;color:#83add9;line-height:1.6}
@media(max-width:1000px){.cfg-grid{grid-template-columns:1fr}.cfg-user{grid-template-columns:1fr 1fr}}
</style>

<div class="cfg-tabs">
    @foreach([
        'smtp'=>'SMTP',
        'telegram'=>'Telegram',
        'users'=>'Usuarios',
        'wisphub'=>'WispHub',
        'mikrowisp'=>'MikroWisp',
        'wispro'=>'Wispro',
        'oss'=>'Conectate OSS'
    ] as $key=>$label)
        <a class="cfg-tab {{ $tab===$key?'active':'' }}" href="{{ route('settings.index',['tab'=>$key]) }}">{{ $label }}</a>
    @endforeach
</div>

@if($tab === 'smtp')
<div class="panel">
    <h2>Servidor de correo (SMTP)</h2>
    <p class="cfg-help">
        Desde aqui configuras el correo con el que el sistema envia cotizaciones y notificaciones.
        Puerto 465 usa SSL directo; 587 usa STARTTLS.
        La contraseña se guarda cifrada en el servidor y nunca se muestra de vuelta.
    </p>

    <form method="POST" action="{{ route('settings.smtp.save') }}">
        @csrf
        <div class="cfg-grid">
            <div class="field"><label>Servidor (host)</label><input name="host" value="{{ $smtp['host'] }}" required placeholder="smtp.empresa.com"></div>
            <div class="field"><label>Puerto</label><input type="number" name="port" value="{{ $smtp['port'] }}" required></div>
            <div class="field"><label>Usuario / correo</label><input name="username" value="{{ $smtp['username'] }}" autocomplete="off"></div>
            <div class="field">
                <label>Contraseña</label>
                <input type="password" name="password" value="" autocomplete="new-password" placeholder="{{ $smtp['password_set'] ? 'Dejar vacio para conservar' : 'Contraseña SMTP' }}">
                @if($smtp['password_set'])<div class="cfg-secret">✓ Contraseña guardada</div>@endif
            </div>
            <div class="field"><label>Remitente (From)</label><input type="email" name="from" value="{{ $smtp['from'] }}" required placeholder="notificaciones@empresa.com"></div>
        </div>
        <button class="btn" type="submit">Guardar SMTP</button>
    </form>

    <hr style="border-color:rgba(90,160,230,.16);margin:20px 0">

    <form method="POST" action="{{ route('settings.smtp.test') }}" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap">
        @csrf
        <div class="field" style="margin:0;min-width:300px">
            <label>Destino de prueba (opcional)</label>
            <input type="email" name="test_to" placeholder="Si se deja vacio usa usuario/remitente">
        </div>
        <button class="btn secondary" type="submit">📧 Enviar correo de prueba</button>
    </form>
</div>
@endif

@if($tab === 'telegram')
<div class="panel">
    <h2>API de Telegram</h2>
    <p class="cfg-help">
        Configura un bot y el Chat ID que recibira las notificaciones del Centro NOC.
        El Bot Token se guarda cifrado y nunca se devuelve al navegador.
    </p>

    <form method="POST" action="{{ route('settings.telegram.save') }}">
        @csrf
        <label class="check" style="margin-bottom:14px">
            <input type="hidden" name="enabled" value="0">
            <input type="checkbox" name="enabled" value="1" {{ $telegram['enabled']?'checked':'' }}>
            Habilitar notificaciones Telegram
        </label>

        <div class="cfg-grid">
            <div class="field">
                <label>Bot Token</label>
                <input type="password" name="bot_token" value="" autocomplete="new-password" placeholder="{{ $telegram['token_set'] ? 'Dejar vacio para conservar' : '123456:ABC...' }}">
                @if($telegram['token_set'])<div class="cfg-secret">✓ Token guardado</div>@endif
            </div>
            <div class="field"><label>Chat ID</label><input name="chat_id" value="{{ $telegram['chat_id'] }}" required></div>
        </div>

        <button class="btn" type="submit">Guardar Telegram</button>
    </form>

    <form method="POST" action="{{ route('settings.telegram.test') }}" style="margin-top:14px">
        @csrf
        <button class="btn secondary" type="submit">Enviar mensaje de prueba</button>
    </form>
</div>
@endif

@if($tab === 'users')
<div class="panel" style="margin-bottom:18px">
    <h2>Crear usuario</h2>
    <p class="cfg-help">
        Roles disponibles: administrador, tecnico y comercial.
        En v0.8.0 estas cuentas quedan creadas y preparadas para control de permisos.
        El Basic Auth actual del proxy se mantiene activo para no bloquear el acceso existente.
    </p>

    <form method="POST" action="{{ route('settings.users.store') }}">
        @csrf
        <div class="cfg-grid">
            <div class="field"><label>Nombre</label><input name="name" required></div>
            <div class="field"><label>Correo</label><input type="email" name="email" required></div>
            <div class="field"><label>Contraseña</label><input type="password" name="password" required minlength="8"></div>
            <div class="field">
                <label>Rol</label>
                <select name="role">
                    <option value="admin">Administrador</option>
                    <option value="tecnico">Tecnico</option>
                    <option value="comercial">Comercial</option>
                </select>
            </div>
        </div>
        <label class="check"><input type="checkbox" name="active" value="1" checked> Activo</label>
        <div><button class="btn" type="submit">Crear usuario</button></div>
    </form>
</div>

<div class="panel">
    <h2>Usuarios del panel</h2>
    @forelse($users as $user)
        <form method="POST" action="{{ route('settings.users.update',$user) }}" class="cfg-user">
            @csrf
            @method('PUT')
            <div class="field" style="margin:0"><label>Nombre</label><input name="name" value="{{ $user->name }}" required></div>
            <div class="field" style="margin:0"><label>Correo</label><input type="email" name="email" value="{{ $user->email }}" required></div>
            <div class="field" style="margin:0">
                <label>Rol</label>
                <select name="role">
                    <option value="admin" {{ $user->role==='admin'?'selected':'' }}>Admin</option>
                    <option value="tecnico" {{ $user->role==='tecnico'?'selected':'' }}>Tecnico</option>
                    <option value="comercial" {{ $user->role==='comercial'?'selected':'' }}>Comercial</option>
                </select>
            </div>
            <div>
                <label class="check"><input type="checkbox" name="active" value="1" {{ $user->active?'checked':'' }}> Activo</label>
            </div>
            <div class="field" style="margin:0"><label>Nueva clave</label><input type="password" name="password" placeholder="Sin cambio"></div>
            <button class="btn secondary" type="submit">Guardar</button>
        </form>
    @empty
        <div class="muted">Aun no hay usuarios internos creados.</div>
    @endforelse
</div>
@endif

@if(in_array($tab,['wisphub','mikrowisp','wispro','oss'],true))
    @php
        $api = $integrations[$tab];
    @endphp
    <div class="panel">
        <h2>API {{ $api['label'] }}</h2>
        <p class="cfg-help">
            Guarda la URL y credenciales sin exponer secretos.
            La ruta de prueba es configurable para no asumir endpoints que cambien entre proveedores o planes.
        </p>

        <form method="POST" action="{{ route('settings.integration.save',$tab) }}">
            @csrf

            <label class="check" style="margin-bottom:14px">
                <input type="hidden" name="enabled" value="0">
                <input type="checkbox" name="enabled" value="1" {{ $api['enabled']?'checked':'' }}>
                Habilitar integracion
            </label>

            <div class="cfg-grid">
                <div class="field"><label>URL base</label><input name="base_url" value="{{ $api['base_url'] }}" required placeholder="https://api.proveedor.com"></div>
                <div class="field">
                    <label>Tipo de autenticacion</label>
                    <select name="auth_type">
                        <option value="bearer" {{ $api['auth_type']==='bearer'?'selected':'' }}>Bearer Token</option>
                        <option value="x-api-key" {{ $api['auth_type']==='x-api-key'?'selected':'' }}>API Key por header</option>
                        <option value="basic" {{ $api['auth_type']==='basic'?'selected':'' }}>Basic Auth</option>
                        <option value="none" {{ $api['auth_type']==='none'?'selected':'' }}>Sin autenticacion</option>
                    </select>
                </div>
                <div class="field"><label>Usuario (si aplica)</label><input name="username" value="{{ $api['username'] }}"></div>
                <div class="field">
                    <label>Token / API Key / Contraseña</label>
                    <input type="password" name="secret" value="" autocomplete="new-password" placeholder="{{ $api['secret_set'] ? 'Dejar vacio para conservar' : 'Credencial secreta' }}">
                    @if($api['secret_set'])<div class="cfg-secret">✓ Credencial guardada</div>@endif
                </div>
                <div class="field"><label>Nombre del header API Key</label><input name="header_name" value="{{ $api['header_name'] }}" placeholder="X-API-Key"></div>
                <div class="field"><label>Ruta de prueba</label><input name="test_path" value="{{ $api['test_path'] }}" placeholder="/api/health o endpoint permitido"></div>
            </div>

            <button class="btn" type="submit">Guardar {{ $api['label'] }}</button>
        </form>

        <form method="POST" action="{{ route('settings.integration.test',$tab) }}" style="margin-top:14px">
            @csrf
            <button class="btn secondary" type="submit">Probar conexion</button>
        </form>
    </div>
@endif

@endsection
