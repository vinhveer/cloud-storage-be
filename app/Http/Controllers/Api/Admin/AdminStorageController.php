<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Support\Facades\DB;

class AdminStorageController extends BaseApiController
{
    public function overview()
    {
        // total users
        $totalUsers = (int) DB::table('users')->count();

        // total files (exclude deleted)
        $totalFiles = (int) DB::table('files')->where('is_deleted', 0)->count();

        // total storage used (sum of users.storage_used; nulls treated as 0)
        $totalStorageUsed = (int) DB::table('users')->sum('storage_used');

        // total storage limit (sum of users.storage_limit; nulls treated as 0)
        $totalStorageLimit = (int) DB::table('users')->sum('storage_limit');

        $overview = [
            'total_users' => $totalUsers,
            'total_files' => $totalFiles,
            'total_storage_used' => $totalStorageUsed,
            'total_storage_limit' => $totalStorageLimit,
            'formatted' => [
                'used' => $this->formatBytes($totalStorageUsed),
                'limit' => $this->formatBytes($totalStorageLimit),
            ],
        ];

        return $this->ok(['system_overview' => $overview]);
    }

    public function users()
    {
        $request = request();

        $search = $request->query('search');
        $page = (int) $request->query('page', 1);
        $perPage = (int) $request->query('per_page', 15);
        $perPage = max(1, min(100, $perPage));

        $query = DB::table('users')
            ->select(['id', 'name', 'email', 'role', 'storage_limit', 'storage_used']);

        if ($search) {
            $like = '%' . strtolower($search) . '%';
            $query->where(function ($q) use ($like) {
                $q->whereRaw('LOWER(name) LIKE ?', [$like])
                  ->orWhereRaw('LOWER(email) LIKE ?', [$like]);
            });
        }

        $paginator = $query->orderBy('id', 'asc')->paginate($perPage, ['*'], 'page', $page);

        $items = [];
        foreach ($paginator->items() as $row) {
            $limit = isset($row->storage_limit) ? (int) $row->storage_limit : 0;
            $used = isset($row->storage_used) ? (int) $row->storage_used : 0;
            $usagePercent = 0.0;
            if ($limit > 0) {
                $usagePercent = round(($used / $limit) * 100, 2);
            }

            $items[] = [
                'user_id' => (int) $row->id,
                'name' => $row->name,
                'email' => $row->email,
                'role' => $row->role,
                'storage_limit' => $limit,
                'storage_used' => $used,
                'usage_percent' => $usagePercent,
            ];
        }

        $pagination = [
            'current_page' => $paginator->currentPage(),
            'total_pages' => $paginator->lastPage(),
            'total_items' => $paginator->total(),
        ];

        return $this->ok([
            'data' => $items,
            'pagination' => $pagination,
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
