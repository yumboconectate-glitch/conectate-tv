<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ApiDocsController;
use App\Http\Controllers\AstraAuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\EpgController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SessionController;
use App\Http\Controllers\SubscriberController;
use App\Http\Controllers\XtreamController;
use App\Http\Controllers\Api\V1\HealthApiController;
use App\Http\Controllers\Api\V1\SubscriberApiController;
use App\Http\Middleware\RequireApiToken;
use App\Http\Middleware\RequirePanelAuth;
use App\Http\Middleware\RequirePanelAdmin;
use App\Models\AstraServer;
use App\Models\Channel;
use App\Models\Subscriber;

use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;


/*
|--------------------------------------------------------------------------
| Middleware retirado de endpoints IPTV
|--------------------------------------------------------------------------
*/

$stateless = [
    EncryptCookies::class,
    AddQueuedCookiesToResponse::class,
    StartSession::class,
    ShareErrorsFromSession::class,
    PreventRequestForgery::class,
];


/*
|--------------------------------------------------------------------------
| Login del panel
|--------------------------------------------------------------------------
*/

Route::get('/login', [
    \App\Http\Controllers\PanelAuthController::class,
    'show'
])->name('login');

Route::post('/login', [
    \App\Http\Controllers\PanelAuthController::class,
    'login'
])->name('login.submit');

Route::post('/logout', [
    \App\Http\Controllers\PanelAuthController::class,
    'logout'
])->name('logout');


/*
|--------------------------------------------------------------------------
| Recursos públicos necesarios para login
|--------------------------------------------------------------------------
*/

Route::get('/brand/logo', function () {
    return response()->file(
        public_path('assets/conectate-logo.jpg'),
        ['Cache-Control' => 'public, max-age=86400']
    );
});


/*
|--------------------------------------------------------------------------
| PANEL ADMINISTRATIVO PROTEGIDO
|--------------------------------------------------------------------------
*/

Route::middleware(RequirePanelAuth::class)->group(function () {

    Route::get('/', DashboardController::class)
        ->name('dashboard');


    /*
    |--------------------------------------------------------------------------
    | Canales
    |--------------------------------------------------------------------------
    */

    Route::get('/channels', [
        ChannelController::class,
        'index'
    ])->name('channels.index');

    Route::put('/channels/{channel}', [
        ChannelController::class,
        'update'
    ])->name('channels.update');


    /*
    |--------------------------------------------------------------------------
    | Categorías
    |--------------------------------------------------------------------------
    */

    Route::get('/categories', [
        CategoryController::class,
        'index'
    ])->name('categories.index');

    Route::post('/categories', [
        CategoryController::class,
        'store'
    ])->name('categories.store');

    Route::put('/categories/{category}', [
        CategoryController::class,
        'update'
    ])->name('categories.update');


    /*
    |--------------------------------------------------------------------------
    | Planes
    |--------------------------------------------------------------------------
    */

    Route::get('/plans', [
        PlanController::class,
        'index'
    ])->name('plans.index');

    Route::post('/plans', [
        PlanController::class,
        'store'
    ])->name('plans.store');

    Route::put('/plans/{plan}', [
        PlanController::class,
        'update'
    ])->name('plans.update');


    /*
    |--------------------------------------------------------------------------
    | Clientes
    |--------------------------------------------------------------------------
    */

    Route::get('/clients', [
        SubscriberController::class,
        'index'
    ])->name('subscribers.index');

    Route::post('/clients', [
        SubscriberController::class,
        'store'
    ])->name('subscribers.store');

    Route::get('/clients/{subscriber}', [
        SubscriberController::class,
        'show'
    ])->name('subscribers.show');

    Route::get('/clients/{subscriber}/edit', [
        SubscriberController::class,
        'edit'
    ])->name('subscribers.edit');

    Route::put('/clients/{subscriber}', [
        SubscriberController::class,
        'update'
    ])->name('subscribers.update');

    Route::post('/clients/{subscriber}/{state}', [
        SubscriberController::class,
        'setState'
    ])
        ->whereIn('state', ['activate', 'suspend'])
        ->name('subscribers.state');

    Route::post('/clients/{subscriber}/sync', [
        SubscriberController::class,
        'sync'
    ])->name('subscribers.sync');

    Route::post('/clients/{subscriber}/rotate', [
        SubscriberController::class,
        'rotateCredentials'
    ])->name('subscribers.rotate');


    /*
    |--------------------------------------------------------------------------
    | Sesiones
    |--------------------------------------------------------------------------
    */

    Route::get('/sessions', [
        SessionController::class,
        'index'
    ])->name('sessions.index');

    Route::post('/sessions/{sessionId}/close', [
        SessionController::class,
        'close'
    ])->name('sessions.close');


    /*
    |--------------------------------------------------------------------------
    | Dispositivos
    |--------------------------------------------------------------------------
    */

    Route::get('/devices', [
        DeviceController::class,
        'index'
    ])->name('devices.index');

    Route::put('/devices/{device}/name', [
        DeviceController::class,
        'rename'
    ])->name('devices.rename');

    Route::post('/devices/{device}/block', [
        DeviceController::class,
        'toggleBlock'
    ])->name('devices.block');

    Route::post('/devices/{device}/ip-block', [
        DeviceController::class,
        'toggleIpBlock'
    ])->name('devices.ip-block');

    Route::post('/devices/{device}/close', [
        DeviceController::class,
        'closeSessions'
    ])->name('devices.close');


    /*
    |--------------------------------------------------------------------------
    | EPG
    |--------------------------------------------------------------------------
    */

    Route::get('/epg', [
        EpgController::class,
        'index'
    ])->name('epg.index');

    Route::post('/epg/sources', [
        EpgController::class,
        'storeSource'
    ])->name('epg.sources.store');

    Route::put('/epg/sources/{source}', [
        EpgController::class,
        'updateSource'
    ])->name('epg.sources.update');

    Route::post('/epg/sources/{source}/import', [
        EpgController::class,
        'import'
    ])->name('epg.sources.import');

    Route::put('/epg/map/{channel}', [
        EpgController::class,
        'map'
    ])->name('epg.map');


    /*
    |--------------------------------------------------------------------------
    | Operación / NOC / Monitor
    |--------------------------------------------------------------------------
    */

    Route::get('/monitor', [
        MonitorController::class,
        'index'
    ])->name('monitor.index');

    Route::get('/activity', [
        ActivityController::class,
        'index'
    ])->name('activity.index');

    Route::get('/api-docs', ApiDocsController::class)
        ->name('api.docs');

    Route::get('/alerts', [
        \App\Http\Controllers\AlertController::class,
        'index'
    ])->name('alerts.index');

    Route::post('/alerts/check', [
        \App\Http\Controllers\AlertController::class,
        'check'
    ])->name('alerts.check');

    Route::post('/alerts/{alert}/ack', [
        \App\Http\Controllers\AlertController::class,
        'acknowledge'
    ])->name('alerts.ack');

    Route::get('/operations', [
        \App\Http\Controllers\OperationController::class,
        'index'
    ])->name('operations.index');

    Route::get('/operations/channels/{channel}', [
        \App\Http\Controllers\OperationController::class,
        'channel'
    ])->name('operations.channel');


    /*
    |--------------------------------------------------------------------------
    | APIs internas del panel
    |--------------------------------------------------------------------------
    */

    Route::get('/api/channels', function () {
        return Channel::with([
            'category',
            'epgSource'
        ])->orderBy('channel_number')->get();
    });

    Route::get('/api/clients', function () {
        return Subscriber::with('plan')->latest()->get();
    });

    Route::get('/api/astra/system', function () {
        $server = AstraServer::first();

        return response()->json([
            'online' => (bool) optional($server)->last_seen_at,
            'server' => $server?->only([
                'name',
                'host',
                'port',
                'last_seen_at',
                'last_system_status',
                'last_error'
            ])
        ]);
    });


    /*
    |--------------------------------------------------------------------------
    | Configuración - SOLO ADMIN
    |--------------------------------------------------------------------------
    */

    Route::middleware(RequirePanelAdmin::class)->group(function () {

        Route::get('/settings', [
            \App\Http\Controllers\SettingsController::class,
            'index'
        ])->name('settings.index');

        Route::post('/settings/smtp', [
            \App\Http\Controllers\SettingsController::class,
            'saveSmtp'
        ])->name('settings.smtp.save');

        Route::post('/settings/smtp/test', [
            \App\Http\Controllers\SettingsController::class,
            'testSmtp'
        ])->name('settings.smtp.test');

        Route::post('/settings/telegram', [
            \App\Http\Controllers\SettingsController::class,
            'saveTelegram'
        ])->name('settings.telegram.save');

        Route::post('/settings/telegram/test', [
            \App\Http\Controllers\SettingsController::class,
            'testTelegram'
        ])->name('settings.telegram.test');

        Route::post('/settings/users', [
            \App\Http\Controllers\SettingsController::class,
            'storeUser'
        ])->name('settings.users.store');

        Route::put('/settings/users/{panelUser}', [
            \App\Http\Controllers\SettingsController::class,
            'updateUser'
        ])->name('settings.users.update');

        Route::post('/settings/integrations/{provider}', [
            \App\Http\Controllers\SettingsController::class,
            'saveIntegration'
        ])->name('settings.integration.save');

        Route::post('/settings/integrations/{provider}/test', [
            \App\Http\Controllers\SettingsController::class,
            'testIntegration'
        ])->name('settings.integration.test');
    });
});


/*
|--------------------------------------------------------------------------
| ASTRA / IPTV / XTREAM
| Estos endpoints NO usan sesión administrativa
|--------------------------------------------------------------------------
*/

Route::get('/astra-auth', AstraAuthController::class)
    ->withoutMiddleware($stateless)
    ->name('astra.auth');

Route::get('/playlist/{token}.m3u', [
    SubscriberController::class,
    'playlist'
])
    ->withoutMiddleware($stateless)
    ->name('playlist');

Route::get('/player_api.php', [
    XtreamController::class,
    'playerApi'
])
    ->withoutMiddleware($stateless)
    ->name('xtream.player');

Route::get('/panel_api.php', [
    XtreamController::class,
    'playerApi'
])
    ->withoutMiddleware($stateless)
    ->name('xtream.panel');

Route::get('/get.php', [
    XtreamController::class,
    'getPlaylist'
])
    ->withoutMiddleware($stateless)
    ->name('xtream.get');

Route::get('/xmltv.php', [
    XtreamController::class,
    'xmltv'
])
    ->withoutMiddleware($stateless)
    ->name('xtream.xmltv');

Route::get('/live/{username}/{password}/{streamId}.m3u8', [
    XtreamController::class,
    'live'
])
    ->withoutMiddleware($stateless)
    ->whereNumber('streamId')
    ->name('xtream.live.m3u8');

Route::get('/live/{username}/{password}/{streamId}.ts', [
    XtreamController::class,
    'live'
])
    ->withoutMiddleware($stateless)
    ->whereNumber('streamId')
    ->name('xtream.live.ts');


/*
|--------------------------------------------------------------------------
| API v1 protegida mediante token
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1')
    ->withoutMiddleware($stateless)
    ->middleware(RequireApiToken::class)
    ->group(function () {

        Route::get('/subscribers', [
            SubscriberApiController::class,
            'index'
        ]);

        Route::post('/subscribers', [
            SubscriberApiController::class,
            'store'
        ]);

        Route::get('/subscribers/{document}', [
            SubscriberApiController::class,
            'status'
        ]);

        Route::post('/subscribers/{document}/activate', [
            SubscriberApiController::class,
            'activate'
        ]);

        Route::post('/subscribers/{document}/suspend', [
            SubscriberApiController::class,
            'suspend'
        ]);

        Route::post('/subscribers/{document}/renew', [
            SubscriberApiController::class,
            'renew'
        ]);

        Route::post('/subscribers/{document}/plan', [
            SubscriberApiController::class,
            'changePlan'
        ]);

        Route::get('/health', HealthApiController::class);
    });


/*
|--------------------------------------------------------------------------
| Health local
|--------------------------------------------------------------------------
*/

Route::get('/api/health', function () {
    return response()->json([
        'ok' => true,
        'service' => 'Conectate TV',
        'version' => '0.9.0',
        'auth_mode' => env('ASTRA_AUTH_MODE', 'middleware'),
        'xmlreader' => class_exists(\XMLReader::class),
        'time' => now()->toIso8601String()
    ]);
});
