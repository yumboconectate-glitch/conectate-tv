<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequirePanelAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->attributes->get('panelUser');

        abort_unless(
            $user && $user->isAdmin(),
            403,
            'Esta sección requiere permisos de administrador.'
        );

        return $next($request);
    }
}
