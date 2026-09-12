<?php

namespace App\Services;

use Illuminate\Support\Str;

class EpgName
{
    public static function normalize(?string $name): string
    {
        $name = Str::upper(Str::ascii((string) $name));
        $name = str_replace('&', ' AND ', $name);
        $name = str_replace('+', ' PLUS ', $name);

        $name = preg_replace(
            '/\b(UHD|FHD|FULL[ -]?HD|HDTV|HD|SD|4K|2160P?|1080P?|720P?)\b/',
            ' ',
            $name
        ) ?? $name;

        $name = preg_replace('/[^A-Z0-9]+/', ' ', $name) ?? $name;

        return trim(preg_replace('/\s+/', ' ', $name) ?? $name);
    }

    public static function variants(?string $name): array
    {
        $base = self::normalize($name);

        if ($base === '') {
            return [];
        }

        $variants = [$base];

        $clean = preg_replace(
            '/\b(IPTV|TLVVD|TLVD|STREAM|FEED)\b/',
            ' ',
            $base
        ) ?? $base;

        $clean = trim(preg_replace('/\s+/', ' ', $clean) ?? $clean);

        if ($clean !== '') {
            $variants[] = $clean;
        }

        $withoutTv = preg_replace('/\bTV\b/', ' ', $clean) ?? $clean;
        $withoutTv = trim(preg_replace('/\s+/', ' ', $withoutTv) ?? $withoutTv);

        if ($withoutTv !== '') {
            $variants[] = $withoutTv;
        }

        $aliases = [
            'NAT GEO' => 'NATIONAL GEOGRAPHIC',
            'NATGEO' => 'NATIONAL GEOGRAPHIC',
            'INVESTIGATION DISCOVERY' => 'ID INVESTIGATION DISCOVERY',
            'E ENTERTAINMENT' => 'E ENTERTAINMENT',
            'SENAL COLOMBIA' => 'SENAL COLOMBIA',
        ];

        foreach ([$base, $clean, $withoutTv] as $candidate) {
            if (isset($aliases[$candidate])) {
                $variants[] = $aliases[$candidate];
            }
        }

        return array_values(array_unique(array_filter($variants)));
    }
}
