<?php

namespace App\Http\Middleware;

use App\Models\PanelUser;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

class RequirePanelAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->session()->get('panel_user_id');

        if (!$userId) {
            return redirect()->guest(route('login'));
        }

        $user = PanelUser::find($userId);

        if (!$user || !$user->active) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('warn','Usuario inactivo o sesión no válida.');
        }

        $timeout = max(5,(int) env('PANEL_SESSION_TIMEOUT_MINUTES',60)) * 60;
        $last = (int) $request->session()->get('panel_last_activity',0);

        if ($last && (time() - $last) > $timeout) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('warn','La sesión expiró por inactividad.');
        }

        $request->session()->put('panel_last_activity',time());

        $request->attributes->set('panelUser',$user);
        View::share('panelUser',$user);

        $response = $next($request);

        $response->headers->set('X-Frame-Options','DENY');
        $response->headers->set('X-Content-Type-Options','nosniff');
        $response->headers->set('Referrer-Policy','same-origin');
        $response->headers->set('Permissions-Policy','camera=(), microphone=(), geolocation=()');
        $response->headers->set('Cache-Control','no-store');

        return $response;
    }
}
