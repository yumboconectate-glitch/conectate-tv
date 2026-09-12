<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">

<title>@yield('title', 'Conectate TV')</title>

<style>
:root{
    --bg:#050b14;
    --panel:#0a1424;
    --panel2:#0d1c32;
    --blue:#0788d2;
    --cyan:#24c7f3;
    --pale:#9edff2;
    --text:#f5fbff;
    --muted:#93a9c1;
    --line:#18385d;
    --ok:#41d392;
    --bad:#ff667a;
    --warn:#ffc857;
    --sidebar:235px;
}

*{
    box-sizing:border-box;
}

html,body{
    margin:0;
    padding:0;
    min-height:100%;
}

body{
    font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 82% 8%,rgba(7,136,210,.22),transparent 25%),
        radial-gradient(circle at 10% 90%,rgba(36,199,243,.12),transparent 22%),
        linear-gradient(145deg,#040910,#071426 60%,#081c36);
    min-height:100vh;
    overflow-x:hidden;
}

button,input,select,textarea{
    font:inherit;
}

img{
    max-width:100%;
}

.shell{
    display:grid;
    grid-template-columns:var(--sidebar) minmax(0,1fr);
    min-height:100vh;
}

/* SIDEBAR */

.side{
    position:sticky;
    top:0;
    height:100vh;
    overflow-y:auto;
    overflow-x:hidden;
    padding:20px 12px;
    border-right:1px solid var(--line);
    background:rgba(3,9,17,.96);
    z-index:60;
}

.side:before,
.side:after{
    content:"";
    position:absolute;
    border:2px solid rgba(36,199,243,.08);
    border-radius:50%;
    width:180px;
    height:115px;
    left:-90px;
    bottom:55px;
    pointer-events:none;
}

.side:after{
    width:140px;
    height:90px;
    left:105px;
    bottom:-20px;
}

.brand{
    position:relative;
    z-index:2;
    background:linear-gradient(
        160deg,
        rgba(255,255,255,.96),
        rgba(158,223,242,.95)
    );
    border-radius:20px;
    padding:7px;
    box-shadow:0 20px 50px rgba(0,110,200,.15);
    margin-bottom:20px;
}

.brand img{
    display:block;
    width:100%;
    height:132px;
    object-fit:cover;
    object-position:center 31%;
    border-radius:15px;
}

.brand small{
    display:block;
    color:#06335f;
    text-align:center;
    padding:4px 0 1px;
    font-weight:800;
}

nav{
    position:relative;
    z-index:2;
}

nav a{
    display:flex;
    gap:11px;
    align-items:center;
    color:#adc2d7;
    text-decoration:none;
    padding:11px 13px;
    border-radius:13px;
    margin:4px 0;
    transition:.15s ease;
}

nav a:hover,
nav a.active{
    color:white;
    background:linear-gradient(
        90deg,
        rgba(7,136,210,.28),
        rgba(36,199,243,.08)
    );
    box-shadow:inset 3px 0 var(--cyan);
}

.icon{
    width:18px;
    flex:0 0 18px;
    text-align:center;
    color:var(--cyan);
}

/* USUARIO */

.userbox{
    position:relative;
    z-index:2;
    margin-top:20px;
    padding:15px 7px 5px;
    border-top:1px solid var(--line);
}

.user-name{
    display:block;
    font-size:13px;
    font-weight:800;
    overflow:hidden;
    white-space:nowrap;
    text-overflow:ellipsis;
}

.user-email{
    display:block;
    margin-top:4px;
    color:var(--muted);
    font-size:11px;
    overflow:hidden;
    white-space:nowrap;
    text-overflow:ellipsis;
}

.user-role{
    display:inline-block;
    margin-top:7px;
    padding:4px 8px;
    border-radius:999px;
    background:rgba(36,199,243,.08);
    border:1px solid rgba(36,199,243,.2);
    color:#bfefff;
    font-size:10px;
    text-transform:uppercase;
    letter-spacing:.08em;
}

.logout{
    width:100%;
    margin-top:11px;
    border:1px solid #214872;
    background:#102844;
    color:#dceaf5;
    border-radius:10px;
    padding:9px 10px;
    cursor:pointer;
    transition:.15s;
}

.logout:hover{
    background:#17395d;
}

/* PRINCIPAL */

.main{
    min-width:0;
    padding:28px 30px;
}

.top{
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:18px;
    margin-bottom:22px;
}

.top-left{
    min-width:0;
}

h1{
    font-size:28px;
    margin:0;
    overflow-wrap:anywhere;
}

.subtitle{
    color:var(--muted);
    margin-top:6px;
}

.badge{
    display:inline-flex;
    align-items:center;
    gap:8px;
    padding:8px 12px;
    border:1px solid rgba(65,211,146,.35);
    background:rgba(65,211,146,.08);
    border-radius:999px;
    color:#bff6db;
    font-size:13px;
    white-space:nowrap;
}

.dot{
    width:8px;
    height:8px;
    border-radius:50%;
    background:var(--ok);
    box-shadow:0 0 16px var(--ok);
}

/* MOBILE BAR */

.mobilebar{
    display:none;
    align-items:center;
    justify-content:space-between;
    margin-bottom:16px;
    gap:10px;
}

.menu-btn{
    display:inline-flex;
    align-items:center;
    gap:8px;
    border:1px solid #214872;
    background:#102844;
    color:white;
    border-radius:11px;
    padding:9px 12px;
    cursor:pointer;
    font-weight:750;
}

.mobile-brand{
    font-weight:800;
    color:#dff6ff;
}

/* OVERLAY */

.sidebar-overlay{
    display:none;
}

/* CARDS */

.cards{
    display:grid;
    grid-template-columns:repeat(5,minmax(130px,1fr));
    gap:14px;
    margin-bottom:20px;
}

.card,
.panel{
    min-width:0;
    background:linear-gradient(
        160deg,
        rgba(14,30,53,.94),
        rgba(8,19,35,.94)
    );
    border:1px solid var(--line);
    border-radius:18px;
    box-shadow:0 16px 40px rgba(0,0,0,.2);
}

.card{
    padding:17px;
}

.card .label{
    font-size:12px;
    color:var(--muted);
    text-transform:uppercase;
    letter-spacing:.08em;
}

.card .num{
    font-size:29px;
    font-weight:800;
    margin-top:7px;
}

.cyan{
    color:var(--cyan);
}

/* PANELES */

.panel{
    padding:18px;
    overflow-x:auto;
}

.panel h2{
    margin:0 0 15px;
    font-size:17px;
}

.grid{
    display:grid;
    grid-template-columns:minmax(0,2fr) minmax(0,1fr);
    gap:18px;
}

.grid2{
    display:grid;
    grid-template-columns:minmax(0,1fr) minmax(0,1fr);
    gap:18px;
}

/* TABLAS */

.table{
    width:100%;
    border-collapse:collapse;
}

.table th{
    color:var(--muted);
    font-size:11px;
    text-transform:uppercase;
    letter-spacing:.08em;
    text-align:left;
    padding:10px;
    white-space:nowrap;
}

.table td{
    padding:11px 10px;
    border-top:1px solid rgba(24,56,93,.7);
    font-size:13px;
    vertical-align:middle;
}

.status{
    display:inline-flex;
    align-items:center;
    gap:6px;
    font-weight:700;
}

.status .s{
    width:7px;
    height:7px;
    border-radius:50%;
}

.on .s{
    background:var(--ok);
}

.off .s{
    background:var(--bad);
}

/* FORMULARIOS */

label{
    display:block;
    color:#b8cde0;
    font-size:12px;
    margin-bottom:6px;
}

.field{
    margin-bottom:13px;
}

input,
select,
textarea{
    width:100%;
    max-width:100%;
    background:#071526;
    color:#fff;
    border:1px solid #214872;
    border-radius:11px;
    padding:10px 11px;
    outline:none;
}

input:focus,
select:focus,
textarea:focus{
    border-color:var(--cyan);
}

textarea{
    min-height:75px;
    resize:vertical;
}

.btn{
    display:inline-flex;
    align-items:center;
    justify-content:center;
    border:0;
    border-radius:11px;
    padding:9px 13px;
    background:linear-gradient(90deg,var(--blue),#0ea8ea);
    color:white;
    font-weight:750;
    text-decoration:none;
    cursor:pointer;
}

.btn.secondary{
    background:#102844;
    border:1px solid #214872;
}

.btn.danger{
    background:#5c1e2b;
    border:1px solid #8d3043;
}

.btn.ok{
    background:#126142;
    border:1px solid #218660;
}

.actions{
    display:flex;
    gap:7px;
    flex-wrap:wrap;
}

/* MENSAJES */

.flash{
    padding:12px 14px;
    border-radius:12px;
    margin-bottom:16px;
    border:1px solid;
}

.flash.ok{
    background:rgba(65,211,146,.08);
    border-color:rgba(65,211,146,.35);
    color:#bff6db;
}

.flash.warn{
    background:rgba(255,200,87,.08);
    border-color:rgba(255,200,87,.35);
    color:#ffe2a0;
}

.checks{
    display:grid;
    grid-template-columns:repeat(2,minmax(0,1fr));
    gap:7px;
    max-height:260px;
    overflow:auto;
    padding:6px;
}

.check{
    display:flex;
    align-items:center;
    gap:8px;
    background:#071526;
    border:1px solid #173958;
    padding:8px;
    border-radius:9px;
}

.check input{
    width:auto;
}

.check label{
    margin:0;
    color:#dceaf5;
    font-size:12px;
}

.metric{
    margin:12px 0;
}

.metric-row{
    display:flex;
    justify-content:space-between;
    color:#cfe1f2;
    font-size:13px;
    margin-bottom:7px;
}

.bar{
    height:8px;
    border-radius:99px;
    background:#102844;
    overflow:hidden;
}

.fill{
    height:100%;
    background:linear-gradient(90deg,var(--blue),var(--cyan));
    border-radius:99px;
}

.muted{
    color:var(--muted);
}

.mono{
    font-family:ui-monospace,SFMono-Regular,Menlo,monospace;
    font-size:12px;
}

.footer{
    color:#607e9b;
    font-size:12px;
    margin-top:20px;
    padding-bottom:10px;
}

/* TABLET */

@media(max-width:1200px){

    .cards{
        grid-template-columns:repeat(3,minmax(130px,1fr));
    }
}

/* MOBILE / TABLET */

@media(max-width:1000px){

    .shell{
        display:block;
    }

    .side{
        position:fixed;
        top:0;
        left:0;
        width:min(86vw,300px);
        height:100vh;
        transform:translateX(-105%);
        transition:transform .22s ease;
        box-shadow:20px 0 60px rgba(0,0,0,.45);
    }

    body.menu-open{
        overflow:hidden;
    }

    body.menu-open .side{
        transform:translateX(0);
    }

    .sidebar-overlay{
        display:block;
        position:fixed;
        inset:0;
        z-index:50;
        background:rgba(0,0,0,.6);
        opacity:0;
        visibility:hidden;
        transition:.2s ease;
    }

    body.menu-open .sidebar-overlay{
        opacity:1;
        visibility:visible;
    }

    .mobilebar{
        display:flex;
    }

    .main{
        padding:17px;
    }

    .top{
        align-items:flex-start;
    }

    .badge{
        display:none;
    }

    .grid,
    .grid2{
        grid-template-columns:1fr;
    }

    .cards{
        grid-template-columns:repeat(2,minmax(0,1fr));
    }
}

/* CELULAR */

@media(max-width:640px){

    .main{
        padding:12px;
    }

    h1{
        font-size:23px;
    }

    .subtitle{
        font-size:13px;
    }

    .top{
        margin-bottom:16px;
    }

    .cards{
        grid-template-columns:1fr;
        gap:10px;
    }

    .card,
    .panel{
        border-radius:15px;
    }

    .panel{
        padding:14px;
    }

    .checks{
        grid-template-columns:1fr;
    }

    .actions{
        align-items:stretch;
    }

    .actions .btn{
        flex:1 1 auto;
    }

    .table{
        min-width:700px;
    }

    input,
    select,
    textarea{
        font-size:16px;
    }
}
</style>

@stack('styles')
</head>

<body>

<div
    class="sidebar-overlay"
    id="sidebarOverlay"
    onclick="closeMenu()">
</div>

<div class="shell">

<aside class="side" id="sidebar">

    <div class="brand">
        <img src="{{ url('/brand/logo') }}" alt="Conectate TV">
        <small>Conectando Sueños · TV</small>
    </div>

    <nav>

        <a
            class="{{ request()->routeIs('dashboard') ? 'active' : '' }}"
            href="{{ route('dashboard') }}">
            <span class="icon">●</span>
            Dashboard
        </a>

        <a
            class="{{ request()->routeIs('channels.*') ? 'active' : '' }}"
            href="{{ route('channels.index') }}">
            <span class="icon">●</span>
            Canales
        </a>

        <a
            class="{{ request()->routeIs('categories.*') ? 'active' : '' }}"
            href="{{ route('categories.index') }}">
            <span class="icon">●</span>
            Categorías
        </a>

        <a
            class="{{ request()->routeIs('subscribers.*') ? 'active' : '' }}"
            href="{{ route('subscribers.index') }}">
            <span class="icon">●</span>
            Clientes
        </a>

        <a
            class="{{ request()->routeIs('plans.*') ? 'active' : '' }}"
            href="{{ route('plans.index') }}">
            <span class="icon">●</span>
            Planes
        </a>

        <a
            class="{{ request()->routeIs('devices.*') ? 'active' : '' }}"
            href="{{ route('devices.index') }}">
            <span class="icon">●</span>
            Dispositivos
        </a>

        <a
            class="{{ request()->routeIs('sessions.*') ? 'active' : '' }}"
            href="{{ route('sessions.index') }}">
            <span class="icon">●</span>
            Sesiones
        </a>

        <a
            class="{{ request()->routeIs('epg.*') ? 'active' : '' }}"
            href="{{ route('epg.index') }}">
            <span class="icon">●</span>
            EPG
        </a>

        <a
            class="{{ request()->routeIs('operations.*') ? 'active' : '' }}"
            href="{{ route('operations.index') }}">
            <span class="icon">●</span>
            Operación IPTV
        </a>

        <a
            class="{{ request()->routeIs('monitor.*') ? 'active' : '' }}"
            href="{{ route('monitor.index') }}">
            <span class="icon">●</span>
            Monitoreo
        </a>

        <a
            class="{{ request()->routeIs('alerts.*') ? 'active' : '' }}"
            href="{{ route('alerts.index') }}">
            <span class="icon">●</span>
            Centro NOC
        </a>

        <a
            class="{{ request()->routeIs('activity.*') ? 'active' : '' }}"
            href="{{ route('activity.index') }}">
            <span class="icon">●</span>
            Actividad
        </a>

        <a
            class="{{ request()->routeIs('api.docs') ? 'active' : '' }}"
            href="{{ route('api.docs') }}">
            <span class="icon">{ }</span>
            API
        </a>

        @if($panelUser->isAdmin())

            <a
                class="{{ request()->routeIs('settings.*') ? 'active' : '' }}"
                href="{{ route('settings.index') }}">
                <span class="icon">●</span>
                Configuración
            </a>

        @endif

    </nav>

    <div class="userbox">

        <span class="user-name">
            {{ $panelUser->name }}
        </span>

        <span class="user-email">
            {{ $panelUser->email }}
        </span>

        <span class="user-role">
            {{ $panelUser->role }}
        </span>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button
                type="submit"
                class="logout">
                Cerrar sesión
            </button>

        </form>

    </div>

</aside>


<main class="main">

    <div class="mobilebar">

        <button
            type="button"
            class="menu-btn"
            onclick="openMenu()">
            ☰ Menú
        </button>

        <div class="mobile-brand">
            Conectate TV
        </div>

    </div>


    <div class="top">

        <div class="top-left">

            <h1>
                @yield('heading','Conectate TV')
            </h1>

            <div class="subtitle">
                @yield('subtitle','Control IPTV propio')
            </div>

        </div>

        <div class="badge">
            <span class="dot"></span>
            Conectate TV v0.9
        </div>

    </div>


    @if(session('ok'))

        <div class="flash ok">
            {{ session('ok') }}
        </div>

    @endif


    @if(session('warn'))

        <div class="flash warn">
            {{ session('warn') }}
        </div>

    @endif


    @if($errors->any())

        <div class="flash warn">
            {{ implode(' · ',$errors->all()) }}
        </div>

    @endif


    @yield('content')


    <div class="footer">
        Conectate TV v0.9 · Plataforma autoalojada · PostgreSQL · Redis · Laravel · NGINX
    </div>

</main>

</div>


<script>

function openMenu(){
    document.body.classList.add('menu-open');
}

function closeMenu(){
    document.body.classList.remove('menu-open');
}

document.addEventListener('keydown',function(e){

    if(e.key === 'Escape'){
        closeMenu();
    }

});

document.querySelectorAll('#sidebar nav a').forEach(function(link){

    link.addEventListener('click',function(){

        if(window.innerWidth <= 1000){
            closeMenu();
        }

    });

});

window.addEventListener('resize',function(){

    if(window.innerWidth > 1000){
        closeMenu();
    }

});

</script>

@stack('scripts')

</body>
</html>
