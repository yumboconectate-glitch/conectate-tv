<?php
return [
    'version' => '0.8.0',

    'alerts' => [
        'astra_stale_minutes' => (int) env('CONNECTATE_TV_ALERT_ASTRA_STALE_MINUTES', 3),
        'offline_minutes' => (int) env('CONNECTATE_TV_ALERT_OFFLINE_MINUTES', 5),
        'cpu_warning' => (float) env('CONNECTATE_TV_ALERT_CPU_WARNING', 85),
        'memory_warning' => (float) env('CONNECTATE_TV_ALERT_MEMORY_WARNING', 85),
        'transport_channel_count' => (int) env('CONNECTATE_TV_ALERT_TRANSPORT_CHANNEL_COUNT', 5),
        'epg_min_stale_hours' => (int) env('CONNECTATE_TV_ALERT_EPG_STALE_HOURS', 12),
        'webhook_url' => env('CONNECTATE_TV_ALERT_WEBHOOK_URL'),
    ],
];
