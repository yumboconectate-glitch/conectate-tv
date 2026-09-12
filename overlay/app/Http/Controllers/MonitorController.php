<?php



namespace App\Http\Controllers;



use App\Models\AstraServer;

use App\Models\Channel;

use App\Services\EpgCoverage;



class MonitorController extends Controller

{

    public function index(EpgCoverage $coverage)

    {

        $server = AstraServer::first();



        $allChannels = Channel::with('category')

            ->where('published', true)

            ->where('astra_stream_id', '<>', '')

            ->get();



        $offline = $allChannels

            ->where('on_air', false)

            ->sortBy('offline_since')

            ->values();



        $online = $allChannels

            ->where('on_air', true)

            ->sortByDesc('sessions')

            ->sortByDesc('bitrate')

            ->take(40)

            ->values();



        $transportErrors = $allChannels

            ->filter(fn ($c) =>

                $c->cc_errors > 0 ||

                $c->pes_errors > 0 ||

                $c->scrambling_errors > 0

            )

            ->sortByDesc('cc_errors')

            ->take(40)

            ->values();



        $analysis = $coverage->analyse($allChannels);



        $guideStates = $analysis['states'];

        $guideStats = $analysis['stats'];

        $coveragePct = $analysis['coverage_pct'];



        return view('monitor', compact(

            'server',

            'offline',

            'online',

            'transportErrors',

            'guideStates',

            'guideStats',

            'coveragePct'

        ));

    }

}
