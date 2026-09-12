<?php

namespace App\Services;

class DeviceFingerprintService
{
    public function key(int $subscriberId, ?string $ip, ?string $userAgent): string
    {
        return hash('sha256', implode('|', [
            $subscriberId,
            strtolower(trim((string) $ip)),
            strtolower(trim((string) $userAgent)),
        ]));
    }

    public function name(?string $userAgent): string
    {
        $ua = strtolower((string) $userAgent);

        return match (true) {
            str_contains($ua, 'tivimate') => 'TiviMate',
            str_contains($ua, 'xciptv') => 'XCIPTV',
            str_contains($ua, 'smarters') => 'IPTV Smarters',
            str_contains($ua, 'vlc') => 'VLC',
            str_contains($ua, 'kodi') => 'Kodi',
            str_contains($ua, 'tizen') => 'Samsung TV',
            str_contains($ua, 'web0s'), str_contains($ua, 'webos') => 'LG webOS TV',
            str_contains($ua, 'android tv') => 'Android TV',
            str_contains($ua, 'android') => 'Android',
            str_contains($ua, 'iphone'), str_contains($ua, 'ipad') => 'iPhone / iPad',
            str_contains($ua, 'windows') => 'Windows',
            str_contains($ua, 'macintosh') => 'macOS',
            default => 'Dispositivo IPTV',
        };
    }
}
