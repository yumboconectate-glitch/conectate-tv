<?php

namespace App\Services;

use App\Models\EpgChannel;
use App\Models\EpgProgramme;
use App\Models\EpgSource;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Throwable;
use XMLReader;

class EpgImporter
{
    public function __construct(private readonly EpgMatcher $matcher) {}

    public function import(EpgSource $source): array
    {
        if (!class_exists(XMLReader::class)) {
            throw new RuntimeException('PHP XMLReader no está disponible. Reconstruye la imagen v0.6.');
        }

        $downloadTmp = tempnam(sys_get_temp_dir(), 'ctv-epg-download-');
        if (!$downloadTmp) {
            throw new RuntimeException('No fue posible crear archivo temporal EPG.');
        }

        $xmlTmp = null;
        $source->update(['last_import_at' => now(), 'last_error' => null]);

        try {
            Http::timeout(180)
                ->retry(2, 900)
                ->withHeaders([
                    'User-Agent' => 'Conectate-TV-EPG/0.6.2',
                    'Accept' => 'application/xml,text/xml,application/gzip,application/octet-stream,*/*',
                ])
                ->withOptions(['sink' => $downloadTmp])
                ->get($source->url)
                ->throw();

            if (!is_file($downloadTmp) || filesize($downloadTmp) < 20) {
                throw new RuntimeException('La fuente XMLTV llegó vacía.');
            }

            $xmlPath = $downloadTmp;

            if ($this->isGzip($downloadTmp)) {
                $xmlTmp = tempnam(sys_get_temp_dir(), 'ctv-epg-xml-');

                if (!$xmlTmp) {
                    throw new RuntimeException('No fue posible crear temporal XML descomprimido.');
                }

                $this->gunzip($downloadTmp, $xmlTmp);
                $xmlPath = $xmlTmp;
            }

            $reader = new XMLReader();

            if (!$reader->open($xmlPath, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE)) {
                throw new RuntimeException('No se pudo abrir XMLTV.');
            }

            $channelCount = 0;
            $programmeCount = 0;
            $programmeBuffer = [];
            $seenIds = [];

            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT) {
                    continue;
                }

                if ($reader->name === 'channel') {
                    $id = trim((string) $reader->getAttribute('id'));

                    if ($id === '') {
                        continue;
                    }

                    $fragment = $reader->readOuterXml();
                    $display = $this->tag($fragment, 'display-name') ?: $id;
                    $icon = $this->icon($fragment);

                    EpgChannel::updateOrCreate(
                        [
                            'epg_source_id' => $source->id,
                            'xmltv_id' => $id,
                        ],
                        [
                            'display_name' => $display,
                            'normalized_name' => EpgName::normalize($display),
                            'icon_url' => $icon,
                            'last_seen_at' => now(),
                        ]
                    );

                    $seenIds[] = $id;
                    $channelCount++;
                    continue;
                }

                if ($reader->name === 'programme') {
                    $xmltvId = trim((string) $reader->getAttribute('channel'));
                    $start = $this->parseDate((string) $reader->getAttribute('start'));
                    $stop = $this->parseDate((string) $reader->getAttribute('stop'));

                    if ($xmltvId === '' || !$start || !$stop) {
                        continue;
                    }

                    if ($stop->lt(now()->subHours(12)) || $start->gt(now()->addDays(14))) {
                        $reader->readOuterXml();
                        continue;
                    }

                    $fragment = $reader->readOuterXml();
                    $title = $this->tag($fragment, 'title') ?: 'Sin título';

                    $programmeBuffer[] = [
                        'epg_source_id' => $source->id,
                        'xmltv_id' => $xmltvId,
                        'start_at' => $start->toIso8601String(),
                        'stop_at' => $stop->toIso8601String(),
                        'title' => $title,
                        'subtitle' => $this->tag($fragment, 'sub-title'),
                        'description' => $this->tag($fragment, 'desc'),
                        'category' => $this->tag($fragment, 'category'),
                        'icon_url' => $this->icon($fragment),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];

                    $programmeCount++;

                    if (count($programmeBuffer) >= 500) {
                        $this->flush($programmeBuffer);
                        $programmeBuffer = [];
                    }
                }
            }

            $reader->close();

            if ($programmeBuffer) {
                $this->flush($programmeBuffer);
            }

            EpgProgramme::where('epg_source_id', $source->id)
                ->where('stop_at', '<', now()->subHours(12))
                ->delete();

            if ($seenIds) {
                EpgChannel::where('epg_source_id', $source->id)
                    ->whereNotIn('xmltv_id', array_unique($seenIds))
                    ->delete();
            }

            $mapped = $this->matcher->autoMap($source);

            $source->update([
                'last_success_at' => now(),
                'last_error' => null,
                'last_channel_count' => $channelCount,
                'last_programme_count' => $programmeCount,
            ]);

            return compact('channelCount', 'programmeCount', 'mapped');
        } catch (Throwable $e) {
            $source->update(['last_error' => $e->getMessage()]);
            throw $e;
        } finally {
            @unlink($downloadTmp);

            if ($xmlTmp) {
                @unlink($xmlTmp);
            }
        }
    }

    private function isGzip(string $path): bool
    {
        $fh = @fopen($path, 'rb');

        if (!$fh) {
            return false;
        }

        $magic = fread($fh, 2);
        fclose($fh);

        return $magic === "\x1f\x8b";
    }

    private function gunzip(string $source, string $destination): void
    {
        $in = @gzopen($source, 'rb');

        if (!$in) {
            throw new RuntimeException('No fue posible abrir la fuente XMLTV gzip.');
        }

        $out = @fopen($destination, 'wb');

        if (!$out) {
            gzclose($in);
            throw new RuntimeException('No fue posible crear XMLTV descomprimido.');
        }

        $written = 0;
        $limit = 200 * 1024 * 1024;

        try {
            while (!gzeof($in)) {
                $chunk = gzread($in, 1024 * 1024);

                if ($chunk === false) {
                    throw new RuntimeException('Error al descomprimir XMLTV.');
                }

                $written += strlen($chunk);

                if ($written > $limit) {
                    throw new RuntimeException('XMLTV descomprimido supera el límite de seguridad de 200 MB.');
                }

                if ($chunk !== '' && fwrite($out, $chunk) === false) {
                    throw new RuntimeException('Error escribiendo XMLTV descomprimido.');
                }
            }
        } finally {
            gzclose($in);
            fclose($out);
        }

        if ($written < 20) {
            throw new RuntimeException('XMLTV gzip se descomprimió vacío.');
        }
    }

    private function flush(array $rows): void
    {
        DB::table('epg_programmes')->upsert(
            $rows,
            ['epg_source_id', 'xmltv_id', 'start_at', 'stop_at'],
            ['title', 'subtitle', 'description', 'category', 'icon_url', 'updated_at']
        );
    }

    private function tag(string $xml, string $tag): ?string
    {
        if (!preg_match(
            '~<'.preg_quote($tag, '~').'(?:\s[^>]*)?>(.*?)</'.preg_quote($tag, '~').'>~si',
            $xml,
            $m
        )) {
            return null;
        }

        return trim(
            html_entity_decode(strip_tags($m[1]), ENT_QUOTES | ENT_XML1, 'UTF-8')
        ) ?: null;
    }

    private function icon(string $xml): ?string
    {
        if (!preg_match('~<icon\b[^>]*\bsrc=["\']([^"\']+)["\']~i', $xml, $m)) {
            return null;
        }

        return html_entity_decode(
            trim($m[1]),
            ENT_QUOTES | ENT_XML1,
            'UTF-8'
        ) ?: null;
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        $value = trim($value);

        if (!preg_match('/^(\d{14})(?:\s*([+-]\d{4}))?/', $value, $m)) {
            return null;
        }

        $tz = $m[2] ?? config('app.timezone', 'America/Bogota');

        try {
            return isset($m[2])
                ? CarbonImmutable::createFromFormat('YmdHis O', $m[1].' '.$m[2])
                : CarbonImmutable::createFromFormat('YmdHis', $m[1], $tz);
        } catch (Throwable) {
            return null;
        }
    }
}
