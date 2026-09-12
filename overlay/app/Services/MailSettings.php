<?php
namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Support\Facades\Mail;

class MailSettings
{
    public function apply(): bool
    {
        $host = trim((string) SystemSetting::read('smtp.host', ''));

        if ($host === '') {
            return false;
        }

        $port = (int) SystemSetting::read('smtp.port', '587');
        $username = (string) SystemSetting::read('smtp.username', '');
        $password = (string) SystemSetting::read('smtp.password', '');
        $from = (string) SystemSetting::read('smtp.from', '');

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp.transport' => 'smtp',
            'mail.mailers.smtp.scheme' => $port === 465 ? 'smtps' : null,
            'mail.mailers.smtp.url' => null,
            'mail.mailers.smtp.host' => $host,
            'mail.mailers.smtp.port' => $port,
            'mail.mailers.smtp.username' => $username !== '' ? $username : null,
            'mail.mailers.smtp.password' => $password !== '' ? $password : null,
            'mail.mailers.smtp.timeout' => 15,
            'mail.from.address' => $from !== '' ? $from : ($username !== '' ? $username : 'noreply@localhost'),
            'mail.from.name' => 'Conectate TV',
        ]);

        return true;
    }

    public function sendTest(?string $to = null): string
    {
        if (!$this->apply()) {
            throw new \RuntimeException('Configura primero el servidor SMTP.');
        }

        $username = (string) SystemSetting::read('smtp.username', '');
        $from = (string) SystemSetting::read('smtp.from', '');

        $destination = trim((string) $to);

        if ($destination === '') {
            $destination = filter_var($username, FILTER_VALIDATE_EMAIL)
                ? $username
                : $from;
        }

        if (!filter_var($destination, FILTER_VALIDATE_EMAIL)) {
            throw new \RuntimeException('No hay un correo valido para recibir la prueba.');
        }

        Mail::purge('smtp');

        Mail::mailer('smtp')->raw(
            'Conectate TV v0.8.0: el servidor SMTP quedo configurado correctamente.',
            function ($message) use ($destination) {
                $message->to($destination)
                    ->subject('Prueba SMTP - Conectate TV');
            }
        );

        return $destination;
    }
}
