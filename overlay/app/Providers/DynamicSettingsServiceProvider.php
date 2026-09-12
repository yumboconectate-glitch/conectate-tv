<?php
namespace App\Providers;

use App\Services\MailSettings;
use Illuminate\Support\ServiceProvider;
use Throwable;

class DynamicSettingsServiceProvider extends ServiceProvider
{
    public function boot(MailSettings $mailSettings): void
    {
        try {
            $mailSettings->apply();
        } catch (Throwable) {
            // Durante builds/migraciones la BD puede no estar disponible.
        }
    }
}
