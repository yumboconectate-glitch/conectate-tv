<?php
namespace App\Http\Controllers;

use App\Models\Channel;
use App\Models\ChannelIncident;
use App\Models\SystemSetting;
use App\Services\AvailabilityService;
use Illuminate\Http\Request;

class OperationController extends Controller
{
    public function index(Request $request, AvailabilityService $availability)
    {
        $allChannels = Channel::with('category')
            ->where('published', true)
            ->where('astra_stream_id', '<>', '')
            ->orderByRaw('channel_number IS NULL')
            ->orderBy('channel_number')
            ->orderBy('name')
            ->get();

        $metrics = $availability->metrics($allChannels);

        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));

        $channels = $allChannels->filter(function ($channel) use ($status, $search) {
            if ($search !== '') {
                $haystack = strtolower(
                    $channel->publicName() . ' '
                    . $channel->astra_stream_id . ' '
                    . ($channel->channel_number ?? '')
                );

                if (!str_contains($haystack, strtolower($search))) {
                    return false;
                }
            }

            if ($status === 'online' && !$channel->on_air) {
                return false;
            }

            if ($status === 'offline' && $channel->on_air) {
                return false;
            }

            if ($status === 'errors') {
                return (int) $channel->cc_errors > 0
                    || (int) $channel->pes_errors > 0
                    || (int) $channel->scrambling_errors > 0;
            }

            return true;
        })->values();

        $problematic = $allChannels
            ->sortByDesc(function ($channel) use ($metrics) {
                $m = $metrics[$channel->id] ?? null;

                return ($m['downtime_7d'] ?? 0)
                    + (($m['incidents_7d'] ?? 0) * 300);
            })
            ->take(10)
            ->values();

        $stats = [
            'total' => $allChannels->count(),
            'online' => $allChannels->where('on_air', true)->count(),
            'offline' => $allChannels->where('on_air', false)->count(),
            'errors' => $allChannels->filter(
                fn ($c) => (int) $c->cc_errors > 0
                    || (int) $c->pes_errors > 0
                    || (int) $c->scrambling_errors > 0
            )->count(),
            'avg_uptime_24h' => round(
                $allChannels->avg(
                    fn ($c) => (float) data_get($metrics, $c->id . '.uptime_24h.percent', 100)
                ) ?? 100,
                3
            ),
        ];

        $monitoringStartedAt = SystemSetting::read('operations.monitoring_started_at');

        return view('operations', compact(
            'channels',
            'allChannels',
            'metrics',
            'problematic',
            'stats',
            'status',
            'search',
            'monitoringStartedAt'
        ));
    }

    public function channel(Channel $channel, AvailabilityService $availability)
    {
        $metrics = $availability->forChannel($channel->id);

        $incidents = ChannelIncident::query()
            ->where('channel_id', $channel->id)
            ->orderByDesc('started_at')
            ->limit(100)
            ->get();

        $monitoringStartedAt = SystemSetting::read('operations.monitoring_started_at');

        return view('operation-channel', compact(
            'channel',
            'metrics',
            'incidents',
            'monitoringStartedAt'
        ));
    }
}
