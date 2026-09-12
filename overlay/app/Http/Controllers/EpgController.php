<?php



namespace App\Http\Controllers;



use App\Models\Channel;

use App\Models\EpgChannel;

use App\Models\EpgProgramme;

use App\Models\EpgSource;

use App\Services\EpgCoverage;

use App\Services\EpgImporter;

use Illuminate\Http\Request;

use Illuminate\Validation\Rule;

use Throwable;



class EpgController extends Controller

{

    public function index(EpgCoverage $coverage)

    {

        $sources = EpgSource::withCount('epgChannels')

            ->orderBy('name')

            ->get();



        $channels = Channel::with(['category','epgSource'])

            ->where('published', true)

            ->where('astra_stream_id', '<>', '')

            ->orderByRaw('channel_number IS NULL')

            ->orderBy('channel_number')

            ->orderBy('name')

            ->get();



        $mappedPairs = $channels

            ->filter(fn ($c) => $c->epg_source_id && trim((string) $c->epg_xmltv_id) !== '')

            ->map(fn ($c) => $c->epg_source_id.'|'.$c->epg_xmltv_id)

            ->unique()

            ->values();



        $epgChannels = collect();



        if ($mappedPairs->isNotEmpty()) {

            $epgChannels = EpgChannel::with('source')

                ->where(function ($q) use ($mappedPairs) {

                    foreach ($mappedPairs as $pair) {

                        [$sourceId, $xmltvId] = explode('|', $pair, 2);



                        $q->orWhere(function ($sub) use ($sourceId, $xmltvId) {

                            $sub->where('epg_source_id', $sourceId)

                                ->where('xmltv_id', $xmltvId);

                        });

                    }

                })

                ->orderBy('display_name')

                ->get();

        }



        $now = now();



        $current = EpgProgramme::where('start_at', '<=', $now)

            ->where('stop_at', '>', $now)

            ->get()

            ->keyBy(fn ($p) => $p->epg_source_id.'|'.$p->xmltv_id);



        $analysis = $coverage->analyse($channels);



        $guideStates = $analysis['states'];

        $guideStats = $analysis['stats'];

        $coveragePct = $analysis['coverage_pct'];



        return view('epg', compact(

            'sources',

            'channels',

            'epgChannels',

            'current',

            'guideStates',

            'guideStats',

            'coveragePct'

        ));

    }



    public function storeSource(Request $request)

    {

        $d = $request->validate([

            'name' => ['required','string','max:160'],

            'url' => ['required','url','max:1000'],

            'refresh_hours' => ['required','integer','min:1','max:72'],

        ]);



        EpgSource::create($d + ['enabled' => true]);



        return back()->with('ok', 'Fuente EPG creada. Ahora pulsa Importar.');

    }



    public function updateSource(Request $request, EpgSource $source)

    {

        $d = $request->validate([

            'name' => ['required','string','max:160'],

            'url' => ['required','url','max:1000'],

            'refresh_hours' => ['required','integer','min:1','max:72'],

            'enabled' => ['nullable','boolean'],

        ]);



        $source->update($d + [

            'enabled' => (bool) ($d['enabled'] ?? false),

        ]);



        return back()->with('ok', 'Fuente EPG actualizada.');

    }



    public function import(EpgSource $source, EpgImporter $importer)

    {

        @set_time_limit(180);



        try {

            $r = $importer->import($source);



            return back()->with(

                'ok',

                "EPG importada: {$r['channelCount']} canales, {$r['programmeCount']} programas, {$r['mapped']} auto-mapeados."

            );

        } catch (Throwable $e) {

            return back()->with('warn', 'Error EPG: '.$e->getMessage());

        }

    }



    public function map(Request $request, Channel $channel)

    {

        $d = $request->validate([

            'epg_channel_id' => ['nullable','exists:epg_channels,id'],

            'epg_kind' => [

                'nullable',

                Rule::in(['radio','own','test']),

            ],

        ]);



        $kind = $d['epg_kind'] ?? null;



        if (empty($d['epg_channel_id'])) {

            $channel->update([

                'epg_source_id' => null,

                'epg_xmltv_id' => null,

                'epg_auto_mapped_at' => null,

                'epg_kind' => $kind,

            ]);

        } else {

            $ec = EpgChannel::findOrFail($d['epg_channel_id']);



            $channel->update([

                'epg_source_id' => $ec->epg_source_id,

                'epg_xmltv_id' => $ec->xmltv_id,

                'epg_auto_mapped_at' => null,

                'epg_kind' => $kind,

                'logo_url' => $channel->logo_url ?: $ec->icon_url,

            ]);

        }



        return back()->with(

            'ok',

            'Mapeo EPG actualizado para '.$channel->publicName().'.'

        );

    }

}
