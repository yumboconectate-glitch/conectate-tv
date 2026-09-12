<?php

namespace App\Http\Controllers;

use App\Models\AuthSession;
use App\Services\AstraSessionService;
use Throwable;

class SessionController extends Controller
{
    public function index(AstraSessionService $sessions)
    {
        $error = null;
        try {
            $sessions->sync();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }

        $active = AuthSession::with(['subscriber.plan', 'channel'])
            ->where('active', true)
            ->orderByDesc('last_seen_at')
            ->get();

        $recent = AuthSession::with(['subscriber.plan', 'channel'])
            ->where('active', false)
            ->whereNotNull('closed_at')
            ->where('closed_at', '>=', now()->subDay())
            ->orderByDesc('closed_at')
            ->limit(50)
            ->get();

        return view('sessions', compact('active', 'recent', 'error'));
    }

    public function close(string $sessionId, AstraSessionService $sessions)
    {
        try {
            $sessions->close($sessionId);
            return back()->with('ok', 'Sesión cerrada en Astra.');
        } catch (Throwable $e) {
            return back()->with('warn', 'No fue posible cerrar la sesión: '.$e->getMessage());
        }
    }
}
