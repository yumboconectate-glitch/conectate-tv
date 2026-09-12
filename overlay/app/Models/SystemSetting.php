<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Throwable;

class SystemSetting extends Model
{
    protected $fillable = ['key', 'value', 'is_secret'];

    protected function casts(): array
    {
        return ['is_secret' => 'boolean'];
    }

    public static function read(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        if (!$row || $row->value === null || $row->value === '') {
            return $default;
        }

        if (!$row->is_secret) {
            return $row->value;
        }

        try {
            return Crypt::decryptString($row->value);
        } catch (Throwable) {
            return $default;
        }
    }

    public static function write(string $key, mixed $value, bool $secret = false): void
    {
        $stored = $value;

        if ($secret && $value !== null && $value !== '') {
            $stored = Crypt::encryptString((string) $value);
        }

        static::query()->updateOrCreate(
            ['key' => $key],
            [
                'value' => $stored === null ? null : (string) $stored,
                'is_secret' => $secret,
            ]
        );
    }

    public static function hasValue(string $key): bool
    {
        $row = static::query()->where('key', $key)->first();

        return (bool) ($row && $row->value !== null && $row->value !== '');
    }

    public static function readBool(string $key, bool $default = false): bool
    {
        $value = static::read($key, $default ? '1' : '0');

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'on'], true);
    }
}
