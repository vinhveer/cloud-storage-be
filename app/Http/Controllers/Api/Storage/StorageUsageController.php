<?php

namespace App\Http\Controllers\Api\Storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorageUsageController extends Controller
{
    /**
     * GET /api/storage/usage
     * Return the user's storage usage. Prefer users.storage_used, else compute runtime SUM(files.file_size).
     * Response (exact shape): { success: true, user_id: int, storage_used: int, formatted: string }
     */
    public function usage(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $storageUsed = null;

        if (isset($user->storage_used) && $user->storage_used !== null) {
            $storageUsed = (int) $user->storage_used;
        } else {
            $storageUsed = (int) DB::table('files')
                ->where('user_id', $user->id)
                ->where('is_deleted', 0)
                ->sum('file_size');
        }

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'storage_used' => $storageUsed,
            'formatted' => $this->formatBytes($storageUsed),
        ]);
    }

    /**
     * Simple bytes -> human readable formatter (binary 1024 base).
     */
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

        // remove trailing .00
        $formatted = preg_replace('/\.00 /', ' ', $formatted);

        return $formatted;
    }
}
