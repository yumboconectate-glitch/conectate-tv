<?php
namespace App\Http\Controllers;

use App\Models\AlertEvent;
use App\Models\Channel;
use App\Services\AlertEngine;
use Illuminate\Http\Request;

class AlertController extends Controller
{
    public function index(Request $request)
    {
        $filter = (string) $request->query('status', 'active');

        $query = AlertEvent::query();

        if (in_array($filter, ['active', 'resolved'], true)) {
            $query->where('status', $filter);
        }

        $alerts = $query
            ->orderByRaw("CASE WHEN status = 'active' THEN 0 ELSE 1 END")
            ->orderByRaw("CASE severity WHEN 'critical' THEN 0 WHEN 'warning' THEN 1 ELSE 2 END")
            ->orderByDesc('last_seen_at')
            ->limit(200)
            ->get();

        $stats = [
            'active' => AlertEvent::where('status', 'active')->count(),
            'critical' => AlertEvent::where('status', 'active')->where('severity', 'critical')->count(),
            'warning' => AlertEvent::where('status', 'active')->where('severity', 'warning')->count(),
            'acknowledged' => AlertEvent::where('status', 'active')->whereNotNull('acknowledged_at')->count(),
            'resolved' => AlertEvent::where('status', 'resolved')->count(),
        ];

        $channelIds = $alerts
            ->where('entity_type', 'channel')
            ->pluck('entity_id')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $channelMap = $channelIds->isEmpty()
            ? collect()
            : Channel::whereIn('id', $channelIds)->get()->keyBy('id');

        $overallState = $stats['critical'] > 0
            ? 'critical'
            : ($stats['warning'] > 0 ? 'warning' : 'normal');

        $overallLabel = match ($overallState) {
            'critical' => 'CRITICO',
            'warning' => 'ADVERTENCIA',
            default => 'NORMAL',
        };

        return view('alerts', compact(
            'alerts',
            'stats',
            'filter',
            'channelMap',
            'overallState',
            'overallLabel'
        ));
    }

    public function check(AlertEngine $engine)
    {
        $result = $engine->run();

        return back()->with(
            'ok',
            'NOC revisado: '
            . $result['active'] . ' activas, '
            . $result['critical'] . ' criticas, '
            . $result['warning'] . ' advertencias.'
        );
    }

    public function acknowledge(AlertEvent $alert)
    {
        if ($alert->status === 'active' && !$alert->acknowledged_at) {
            $alert->update(['acknowledged_at' => now()]);
        }

        return back()->with('ok', 'Alerta reconocida.');
    }
}
