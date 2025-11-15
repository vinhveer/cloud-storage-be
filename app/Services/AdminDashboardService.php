<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\PublicLink;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AdminDashboardService
{
    public function overview(): array
    {
        $totalUsers = (int) User::count();
        $totalFiles = (int) File::where('is_deleted', false)->count();
        $totalStorageUsed = (int) User::sum('storage_used');
        $averagePerUser = $totalUsers > 0 ? (int) floor($totalStorageUsed / $totalUsers) : 0;

        $now = Carbon::now();
        $activePublicLinks = (int) PublicLink::whereNull('revoked_at')
            ->where(function ($q) use ($now) {
                $q->whereNull('expired_at')->orWhere('expired_at', '>', $now);
            })
            ->count();

        $recentUsers = User::orderByDesc('id')
            ->limit(5)
            ->get(['id', 'name', 'email', 'created_at'])
            ->map(fn($u) => [
                'user_id' => $u->id,
                'name' => $u->name,
                'email' => $u->email,
                'created_at' => $u->created_at?->toIso8601String(),
            ])
            ->all();

        return [
            'total_users' => $totalUsers,
            'total_files' => $totalFiles,
            'total_storage_used' => $totalStorageUsed,
            'average_storage_per_user' => $averagePerUser,
            'active_public_links' => $activePublicLinks,
            'recent_users' => $recentUsers,
        ];
    }

    public function users(): array
    {
        $roles = User::select('role', DB::raw('count(*) as count'))
            ->groupBy('role')->pluck('count', 'role')->toArray();

        // Buckets in bytes
        $oneGb = 1024 * 1024 * 1024;
        $dist = [
            '0–1GB' => (int) User::where('storage_used', '>=', 0)->where('storage_used', '<', $oneGb)->count(),
            '1–5GB' => (int) User::where('storage_used', '>=', $oneGb)->where('storage_used', '<', 5 * $oneGb)->count(),
            '5GB+' => (int) User::where('storage_used', '>=', 5 * $oneGb)->count(),
        ];

        $newUsers7d = (int) User::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        return [
            'roles' => [
                'admin' => (int) ($roles['admin'] ?? 0),
                'user' => (int) ($roles['user'] ?? 0),
            ],
            'storage_usage_distribution' => [
                ['range' => '0–1GB', 'users' => $dist['0–1GB']],
                ['range' => '1–5GB', 'users' => $dist['1–5GB']],
                ['range' => '5GB+', 'users' => $dist['5GB+']],
            ],
            'new_users_last_7_days' => $newUsers7d,
        ];
    }

    public function files(): array
    {
        $fileExtStats = File::where('is_deleted', false)
            ->select('file_extension', DB::raw('count(*) as count'))
            ->groupBy('file_extension')
            ->orderByDesc('count')
            ->get()
            ->map(fn($r) => [
                'extension' => (string) ($r->file_extension ?? ''),
                'count' => (int) $r->count,
            ])
            ->all();

        $deletedFiles = (int) File::where(function ($q) {
            $q->where('is_deleted', true)->orWhereNotNull('deleted_at');
        })->count();

        $totalStorageUsed = (int) User::sum('storage_used');

        return [
            'file_extension_stats' => $fileExtStats,
            'deleted_files' => $deletedFiles,
            'total_storage_used' => $totalStorageUsed,
        ];
    }

    public function storage(): array
    {
        $days = 30;
        $start = Carbon::now()->subDays($days - 1)->startOfDay();
        $raw = FileVersion::where('created_at', '>=', $start)
            ->select(DB::raw('DATE(created_at) as d'), DB::raw('SUM(file_size) as bytes'))
            ->groupBy('d')
            ->orderBy('d')
            ->get()
            ->mapWithKeys(fn($r) => [$r->d => (int) $r->bytes]);

        // Build daily timeline and cumulative total
        $timeline = [];
        $cumulative = 0;
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i)->format('Y-m-d');
            $added = (int) ($raw[$date] ?? 0);
            $cumulative += $added;
            $timeline[] = [
                'date' => $date,
                'total_storage_used' => $cumulative,
            ];
        }

        $avgGrowth = $days > 0 ? (int) floor(array_sum(array_map(fn($e) => $e['total_storage_used'], $timeline)) / $days) : 0;

        return [
            'storage_timeline' => $timeline,
            'average_growth_per_day' => $avgGrowth,
        ];
    }

    /**
     * Activity derived from FileVersion table.
     */
    public function activity(?string $startDate, ?string $endDate, ?string $action, int $page, int $perPage): array
    {
        $query = DB::table('file_versions as fv')
            ->leftJoin('users as u', 'u.id', '=', 'fv.user_id')
            ->leftJoin('files as f', 'f.id', '=', 'fv.file_id')
            ->select([
                'fv.id as activity_id',
                'fv.user_id',
                'u.name as user_name',
                'fv.action',
                DB::raw('COALESCE(f.display_name, fv.uuid) as target'),
                'fv.created_at',
            ])
            ->orderByDesc('fv.created_at');

        if ($startDate) {
            $query->whereDate('fv.created_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->whereDate('fv.created_at', '<=', $endDate);
        }
        if ($action) {
            $query->where('fv.action', $action);
        }

        $total = (int) (clone $query)->count();
        $items = $query->forPage($page, $perPage)->get()->map(fn($r) => [
            'activity_id' => (int) $r->activity_id,
            'user_id' => (int) $r->user_id,
            'user_name' => (string) $r->user_name,
            'action' => (string) $r->action,
            'target' => (string) $r->target,
            'created_at' => Carbon::parse($r->created_at)->toIso8601String(),
        ])->all();

        $totalPages = (int) ceil($total / max(1, $perPage));

        return [
            'data' => $items,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ];
    }
}
