<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex,nofollow">
<title>Ingreso | Conectate TV</title>
<style>
:root{
    --blue:#0788d2;
    --cyan:#24c7f3;
    --text:#f5fbff;
    --muted:#93a9c1;
    --line:#18385d;
}
*{box-sizing:border-box}
body{
    margin:0;
    min-height:100vh;
    display:grid;
    place-items:center;
    padding:20px;
    font-family:Inter,system-ui,-apple-system,"Segoe UI",sans-serif;
    color:var(--text);
    background:
      radial-gradient(circle at 80% 10%,rgba(7,136,210,.25),transparent 30%),
      linear-gradient(145deg,#040910,#071426 60%,#081c36);
}
.card{
    width:min(100%,430px);
    background:linear-gradient(160deg,#0e1e35,#081323);
    border:1px solid var(--line);
    border-radius:24px;
    padding:27px;
    box-shadow:0 30px 80px rgba(0,0,0,.4);
}
.logo{
    max-width:250px;
    margin:0 auto 20px;
    padding:7px;
    background:white;
    border-radius:18px;
}
.logo img{
    display:block;
    width:100%;
    height:115px;
    object-fit:cover;
    border-radius:13px;
}
h1{
    margin:0;
    text-align:center;
    font-size:26px;
}
.sub{
    text-align:center;
    color:var(--muted);
    margin:7px 0 24px;
}
.field{margin-bottom:15px}
label{
    display:block;
    margin-bottom:7px;
    font-size:13px;
    color:#c9dae8;
}
input{
    width:100%;
    padding:12px 13px;
    border-radius:12px;
    border:1px solid #214872;
    background:#071526;
    color:white;
    font-size:16px;
    outline:none;
}
input:focus{
    border-color:var(--cyan);
    box-shadow:0 0 0 3px rgba(36,199,243,.1);
}
.btn{
    width:100%;
    border:0;
    padding:12px;
    border-radius:12px;
    color:white;
    background:linear-gradient(90deg,var(--blue),#0ea8ea);
    font-weight:800;
    cursor:pointer;
}
.flash{
    padding:11px 12px;
    margin-bottom:15px;
    border-radius:10px;
    background:#47202a;
    border:1px solid #8d3043;
}
.foot{
    text-align:center;
    color:#607e9b;
    font-size:12px;
    margin-top:18px;
}
@media(max-width:480px){
    body{padding:12px}
    .card{padding:20px;border-radius:18px}
    .logo{max-width:205px}
}
</style>
</head>
<body>

<div class="card">
    <div class="logo">
        <img src="{{ url('/brand/logo') }}" alt="Conectate TV">
    </div>

    <h1>Conectate TV</h1>
    <div class="sub">Panel administrativo</div>

    @if(session('warn'))
        <div class="flash">{{ session('warn') }}</div>
    @endif

    @if($errors->any())
        <div class="flash">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('login.submit') }}">
        @csrf

        <div class="field">
            <label>Correo electrónico</label>
            <input
                type="email"
                name="email"
                value="{{ old('email') }}"
                autocomplete="username"
                required
                autofocus>
        </div>

        <div class="field">
            <label>Contraseña</label>
            <input
                type="password"
                name="password"
                autocomplete="current-password"
                required>
        </div>

        <button class="btn" type="submit">Ingresar</button>
    </form>

    <div class="foot">
        Acceso administrativo restringido<br>
        Conectate TV
    </div>
</div>

</body>
</html>
