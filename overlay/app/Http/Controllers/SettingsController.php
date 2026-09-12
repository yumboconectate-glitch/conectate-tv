<?php
namespace App\Http\Controllers;

use App\Models\PanelUser;
use App\Models\SystemSetting;
use App\Services\MailSettings;
use App\Services\TelegramSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Throwable;

class SettingsController extends Controller
{
    private const PROVIDERS = [
        'wisphub' => 'WispHub',
        'mikrowisp' => 'MikroWisp',
        'wispro' => 'Wispro',
        'oss' => 'Conectate OSS',
    ];

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'smtp');

        $allowedTabs = array_merge(
            ['smtp', 'telegram', 'users'],
            array_keys(self::PROVIDERS)
        );

        if (!in_array($tab, $allowedTabs, true)) {
            $tab = 'smtp';
        }

        $smtp = [
            'host' => (string) SystemSetting::read('smtp.host', ''),
            'port' => (int) SystemSetting::read('smtp.port', '587'),
            'username' => (string) SystemSetting::read('smtp.username', ''),
            'from' => (string) SystemSetting::read('smtp.from', ''),
            'password_set' => SystemSetting::hasValue('smtp.password'),
        ];

        $telegram = [
            'enabled' => SystemSetting::readBool('telegram.enabled', false),
            'chat_id' => (string) SystemSetting::read('telegram.chat_id', ''),
            'token_set' => SystemSetting::hasValue('telegram.bot_token'),
        ];

        $users = PanelUser::orderBy('name')->get();

        $integrations = [];

        foreach (self::PROVIDERS as $key => $label) {
            $prefix = 'integration.' . $key . '.';

            $integrations[$key] = [
                'label' => $label,
                'enabled' => SystemSetting::readBool($prefix . 'enabled', false),
                'base_url' => (string) SystemSetting::read(
                    $prefix . 'base_url',
                    $key === 'oss' ? 'https://oss.conectate.com.co' : ''
                ),
                'auth_type' => (string) SystemSetting::read($prefix . 'auth_type', 'bearer'),
                'username' => (string) SystemSetting::read($prefix . 'username', ''),
                'secret_set' => SystemSetting::hasValue($prefix . 'secret'),
                'header_name' => (string) SystemSetting::read($prefix . 'header_name', 'X-API-Key'),
                'test_path' => (string) SystemSetting::read($prefix . 'test_path', ''),
            ];
        }

        return view('settings', compact(
            'tab',
            'smtp',
            'telegram',
            'users',
            'integrations'
        ));
    }

    public function saveSmtp(Request $request)
    {
        $data = $request->validate([
            'host' => ['required', 'string', 'max:255'],
            'port' => ['required', 'integer', 'min:1', 'max:65535'],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:1000'],
            'from' => ['required', 'email', 'max:255'],
        ]);

        SystemSetting::write('smtp.host', trim($data['host']));
        SystemSetting::write('smtp.port', (string) $data['port']);
        SystemSetting::write('smtp.username', trim((string) ($data['username'] ?? '')));
        SystemSetting::write('smtp.from', trim($data['from']));

        if (filled($data['password'] ?? null)) {
            SystemSetting::write('smtp.password', $data['password'], true);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'smtp'])
            ->with('ok', 'Configuracion SMTP guardada.');
    }

    public function testSmtp(Request $request, MailSettings $mailSettings)
    {
        $data = $request->validate([
            'test_to' => ['nullable', 'email', 'max:255'],
        ]);

        try {
            $to = $mailSettings->sendTest($data['test_to'] ?? null);

            return back()->with('ok', 'Correo de prueba enviado a ' . $to . '.');
        } catch (Throwable $e) {
            return back()->with('warn', 'Prueba SMTP: ' . $e->getMessage());
        }
    }

    public function saveTelegram(Request $request)
    {
        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'bot_token' => ['nullable', 'string', 'max:1000'],
            'chat_id' => ['required', 'string', 'max:255'],
        ]);

        SystemSetting::write(
            'telegram.enabled',
            !empty($data['enabled']) ? '1' : '0'
        );

        SystemSetting::write('telegram.chat_id', trim($data['chat_id']));

        if (filled($data['bot_token'] ?? null)) {
            SystemSetting::write('telegram.bot_token', $data['bot_token'], true);
        }

        return redirect()
            ->route('settings.index', ['tab' => 'telegram'])
            ->with('ok', 'Configuracion Telegram guardada.');
    }

    public function testTelegram(TelegramSettings $telegram)
    {
        try {
            $telegram->sendTest();

            return back()->with('ok', 'Mensaje de prueba enviado a Telegram.');
        } catch (Throwable $e) {
            return back()->with('warn', 'Prueba Telegram: ' . $e->getMessage());
        }
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => ['required', 'email', 'max:255', 'unique:panel_users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'role' => ['required', Rule::in(PanelUser::ROLES)],
            'active' => ['nullable', 'boolean'],
        ]);

        PanelUser::create([
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'password' => Hash::make($data['password']),
            'role' => $data['role'],
            'active' => !empty($data['active']),
        ]);

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('ok', 'Usuario creado.');
    }

    public function updateUser(Request $request, PanelUser $panelUser)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:160'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('panel_users', 'email')->ignore($panelUser->id),
            ],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'role' => ['required', Rule::in(PanelUser::ROLES)],
            'active' => ['nullable', 'boolean'],
        ]);

        $update = [
            'name' => trim($data['name']),
            'email' => strtolower(trim($data['email'])),
            'role' => $data['role'],
            'active' => !empty($data['active']),
        ];

        if (filled($data['password'] ?? null)) {
            $update['password'] = Hash::make($data['password']);
        }

        $panelUser->update($update);

        return redirect()
            ->route('settings.index', ['tab' => 'users'])
            ->with('ok', 'Usuario actualizado.');
    }

    public function saveIntegration(Request $request, string $provider)
    {
        $this->providerLabel($provider);

        $data = $request->validate([
            'enabled' => ['nullable', 'boolean'],
            'base_url' => ['required', 'url', 'max:1000'],
            'auth_type' => ['required', Rule::in(['bearer', 'x-api-key', 'basic', 'none'])],
            'username' => ['nullable', 'string', 'max:255'],
            'secret' => ['nullable', 'string', 'max:2000'],
            'header_name' => ['nullable', 'string', 'max:255'],
            'test_path' => ['nullable', 'string', 'max:1000'],
        ]);

        $prefix = 'integration.' . $provider . '.';

        SystemSetting::write($prefix . 'enabled', !empty($data['enabled']) ? '1' : '0');
        SystemSetting::write($prefix . 'base_url', rtrim($data['base_url'], '/'));
        SystemSetting::write($prefix . 'auth_type', $data['auth_type']);
        SystemSetting::write($prefix . 'username', trim((string) ($data['username'] ?? '')));
        SystemSetting::write($prefix . 'header_name', trim((string) ($data['header_name'] ?? 'X-API-Key')));
        SystemSetting::write($prefix . 'test_path', trim((string) ($data['test_path'] ?? '')));

        if (filled($data['secret'] ?? null)) {
            SystemSetting::write($prefix . 'secret', $data['secret'], true);
        }

        return redirect()
            ->route('settings.index', ['tab' => $provider])
            ->with('ok', $this->providerLabel($provider) . ' guardado.');
    }

    public function testIntegration(string $provider)
    {
        $label = $this->providerLabel($provider);
        $prefix = 'integration.' . $provider . '.';

        $base = rtrim((string) SystemSetting::read($prefix . 'base_url', ''), '/');
        $path = trim((string) SystemSetting::read($prefix . 'test_path', ''));
        $authType = (string) SystemSetting::read($prefix . 'auth_type', 'bearer');
        $username = (string) SystemSetting::read($prefix . 'username', '');
        $secret = (string) SystemSetting::read($prefix . 'secret', '');
        $headerName = (string) SystemSetting::read($prefix . 'header_name', 'X-API-Key');

        if ($base === '') {
            return back()->with('warn', $label . ': configura primero la URL base.');
        }

        $url = $base;

        if ($path !== '') {
            $url .= '/' . ltrim($path, '/');
        }

        try {
            $http = Http::timeout(10)->acceptJson();

            if ($authType === 'bearer' && $secret !== '') {
                $http = $http->withToken($secret);
            } elseif ($authType === 'x-api-key' && $secret !== '') {
                $http = $http->withHeaders([$headerName ?: 'X-API-Key' => $secret]);
            } elseif ($authType === 'basic') {
                $http = $http->withBasicAuth($username, $secret);
            }

            $response = $http->get($url);

            if ($response->successful()) {
                return back()->with(
                    'ok',
                    $label . ' conectado correctamente (HTTP ' . $response->status() . ').'
                );
            }

            return back()->with(
                'warn',
                $label . ' respondio HTTP ' . $response->status() . '.'
            );
        } catch (Throwable $e) {
            return back()->with('warn', $label . ': ' . $e->getMessage());
        }
    }

    private function providerLabel(string $provider): string
    {
        if (!array_key_exists($provider, self::PROVIDERS)) {
            abort(404);
        }

        return self::PROVIDERS[$provider];
    }
}
