<?php

namespace App\Http\Controllers\Api\Storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorageLimitController extends Controller
{
    /**
     * GET /api/storage/limit
     * Return user's storage limit, usage and remaining. Prefer users.storage_limit else fallback to
     * system_configs.default_storage_limit or 10GB.
     * Response shape (exact top-level JSON):
     * { success: true, user_id, storage_limit, storage_used, remaining, formatted: { limit, used, remaining } }
     */
    public function limit(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        // Determine storage limit (prefer user field)
        $storageLimit = null;
        if (isset($user->storage_limit) && $user->storage_limit !== null) {
            $storageLimit = (int) $user->storage_limit;
        } else {
            $conf = DB::table('system_configs')->where('config_key', 'default_storage_limit')->value('config_value');
            if ($conf !== null) {
                $storageLimit = (int) $conf;
            } else {
                // safe fallback to 10 GB
                $storageLimit = 10737418240; // 10 * 1024^3
            }
        }

        // Determine storage used (prefer user's cached value)
        if (isset($user->storage_used) && $user->storage_used !== null) {
            $storageUsed = (int) $user->storage_used;
        } else {
            $storageUsed = (int) DB::table('files')
                ->where('user_id', $user->id)
                ->where('is_deleted', 0)
                ->sum('file_size');
        }

        $remaining = max(0, $storageLimit - $storageUsed);

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'storage_limit' => $storageLimit,
            'storage_used' => $storageUsed,
            'remaining' => $remaining,
            'formatted' => [
                'limit' => $this->formatBytes($storageLimit),
                'used' => $this->formatBytes($storageUsed),
                'remaining' => $this->formatBytes($remaining),
            ],
        ]);
    }

    protected function formatBytes(int $bytes, int $decimals = 2): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $k = 1024;
        $sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $i = (int) floor(log($bytes) / log($k));
        $i = max(0, min($i, count($sizes) - 1));

        $value = $bytes / pow($k, $i);
        $formatted = round($value, $decimals) . ' ' . $sizes[$i];
        $formatted = preg_replace('/\.00 /', ' ', $formatted);

        return $formatted;
    }
}
