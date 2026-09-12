<?php



namespace App\Http\Controllers;



use App\Models\ActivityLog;

use App\Models\AstraServer;

use App\Models\AuthSession;

use App\Models\Channel;

use App\Models\Device;

use App\Models\EpgSource;

use App\Models\Plan;

use App\Models\Subscriber;



class DashboardController extends Controller

{

    public function __invoke()

    {

        $server = AstraServer::first();



        $channels = Channel::with(['category','epgSource'])

            ->orderByRaw('channel_number IS NULL')

            ->orderBy('channel_number')

            ->orderBy('name')

            ->get();



        $published = $channels->where('published', true);



        $stats = [

            'total' => $channels->count(),

            'published' => $published->count(),

            'onair' => $published->where('on_air', true)->count(),

            'offline' => $published->where('on_air', false)->count(),



            'offline5' => $published

                ->where('on_air', false)

                ->filter(

                    fn ($c) =>

                        $c->offline_since &&

                        $c->offline_since->lte(now()->subMinutes(5))

                )

                ->count(),



            'sessions' => AuthSession::where('active', true)->count(),



            'clients' => Subscriber::count(),



            'active_clients' => Subscriber::where('active', true)

                ->where(

                    fn ($q) =>

                        $q->whereNull('expires_at')

                          ->orWhere('expires_at', '>', now())

                )

                ->count(),



            'expired' => Subscriber::whereNotNull('expires_at')

                ->where('expires_at', '<=', now())

                ->count(),



            'expiring7' => Subscriber::whereNotNull('expires_at')

                ->whereBetween(

                    'expires_at',

                    [now(), now()->addDays(7)]

                )

                ->count(),



            'plans' => Plan::where('active', true)->count(),



            'devices' => Device::count(),



            'blocked_devices' => Device::where('blocked', true)->count(),



            'epg_sources' => EpgSource::where('enabled', true)->count(),



            'epg_mapped' => $published

                ->filter(

                    fn ($c) =>

                        trim((string) $c->epg_xmltv_id) !== ''

                )

                ->count(),



            'total_bitrate' => $published

                ->where('on_air', true)

                ->sum('bitrate'),

        ];





        /*

         * Astra system-status

         */



        $rawStatus = $server?->last_system_status;



        if (is_string($rawStatus)) {

            $systemStatus = json_decode($rawStatus, true) ?: [];

        } elseif (is_array($rawStatus)) {

            $systemStatus = $rawStatus;

        } elseif (is_object($rawStatus)) {

            $systemStatus = (array) $rawStatus;

        } else {

            $systemStatus = [];

        }





        $formatUptime = function ($seconds): string {

            $seconds = (int) $seconds;



            if ($seconds <= 0) {

                return '-';

            }



            $days = intdiv($seconds, 86400);

            $seconds %= 86400;



            $hours = intdiv($seconds, 3600);

            $seconds %= 3600;



            $minutes = intdiv($seconds, 60);



            $parts = [];



            if ($days > 0) {

                $parts[] = $days.'d';

            }



            if ($hours > 0) {

                $parts[] = $hours.'h';

            }



            if ($minutes > 0 || empty($parts)) {

                $parts[] = $minutes.'m';

            }



            return implode(' ', $parts);

        };





        $astraMetrics = [



            'online' =>

                $server &&

                !$server->last_error &&

                $server->last_seen_at,



            'app_cpu' =>

                (int) ($systemStatus['app_cpu_usage'] ?? 0),



            'sys_cpu' =>

                (int) ($systemStatus['sys_cpu_usage'] ?? 0),



            'app_mem_percent' =>

                (int) ($systemStatus['app_mem_usage'] ?? 0),



            'sys_mem_percent' =>

                (int) ($systemStatus['sys_mem_usage'] ?? 0),



            'app_mem_gb' =>

                isset($systemStatus['app_mem_kb'])

                    ? round(

                        ((float) $systemStatus['app_mem_kb']) / 1048576,

                        2

                    )

                    : 0,



            'threads' =>

                (int) ($systemStatus['app_threads'] ?? 0),



            'app_uptime' =>

                $formatUptime(

                    $systemStatus['app_uptime'] ?? 0

                ),



            'sys_uptime' =>

                $formatUptime(

                    $systemStatus['sys_uptime'] ?? 0

                ),



            'la1' =>

                isset($systemStatus['la1'])

                    ? ((float) $systemStatus['la1']) / 100

                    : 0,



            'la5' =>

                isset($systemStatus['la5'])

                    ? ((float) $systemStatus['la5']) / 100

                    : 0,



            'la15' =>

                isset($systemStatus['la15'])

                    ? ((float) $systemStatus['la15']) / 100

                    : 0,

        ];





        $recentActivity = ActivityLog::with('subscriber')

            ->orderByDesc('created_at')

            ->limit(8)

            ->get();





        return view(

            'dashboard',

            compact(

                'server',

                'channels',

                'stats',

                'recentActivity',

                'astraMetrics'

            )

        );

    }

}
