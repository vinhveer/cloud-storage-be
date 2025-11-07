<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'config_key', 'config_value'
    ];

    protected $hidden = [];
    protected $casts = [];

    /**
     * Retrieve a system config value and return it as an integer number of bytes.
     *
     * Acceptable stored forms in `config_value`:
     * - integer string representing bytes (e.g. "52428800")
     * - human readable with unit suffix (e.g. "50MB", "10GB", "5MiB")
     *
     * @param string $key
     * @param int $default
     * @return int
     */
    public static function getBytes(string $key, int $default = 0): int
    {
        $raw = (string) (static::where('config_key', $key)->value('config_value') ?? '');
        if ($raw === '') {
            return $default;
        }

        // If purely numeric, treat as bytes
        if (preg_match('/^\d+$/', trim($raw))) {
            return (int) $raw;
        }

        // Parse human-readable sizes like 50MB, 10GB, 5MiB
        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*(k|kb|kib|m|mb|mib|g|gb|gib|t|tb|tib)?\s*$/i', $raw, $m)) {
            $num = (float) $m[1];
            $unit = isset($m[2]) ? strtolower($m[2]) : '';

            switch ($unit) {
                case 't': case 'tb': case 'tib':
                    $bytes = $num * 1024 * 1024 * 1024 * 1024;
                    break;
                case 'g': case 'gb': case 'gib':
                    $bytes = $num * 1024 * 1024 * 1024;
                    break;
                case 'm': case 'mb': case 'mib':
                    $bytes = $num * 1024 * 1024;
                    break;
                case 'k': case 'kb': case 'kib':
                    $bytes = $num * 1024;
                    break;
                default:
                    $bytes = $num; // fallback
            }

            return (int) floor($bytes);
        }

        // Fallback: return default
        return $default;
    }
}
