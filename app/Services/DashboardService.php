<?php

namespace App\Services;

use App\Models\File;
use App\Models\FileVersion;
use App\Models\Folder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function getOverview(User $user): array
    {
        $filesCount = File::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->count();

        $foldersCount = Folder::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->count();

        $storageUsed = (int) ($user->storage_used ?? 0);
        $storageLimit = (int) ($user->storage_limit ?? 0);
        $usagePercent = $storageLimit > 0 ? round(($storageUsed / $storageLimit) * 100, 2) : 0.0;

        $recentActivityCount = File::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->whereNotNull('last_opened_at')
            ->count();

        return [
            'files_count' => $filesCount,
            'folders_count' => $foldersCount,
            'storage_used' => $storageUsed,
            'storage_limit' => $storageLimit,
            'storage_usage_percent' => $usagePercent,
            'recent_activity_count' => (int) $recentActivityCount,
        ];
    }

    public function getRecent(User $user, int $limit = 10): array
    {
        $limit = max(1, min($limit, 100));

        $files = File::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->select(['id', 'display_name', 'mime_type', 'file_size', 'last_opened_at', 'created_at'])
            ->get()
            ->map(function (File $f) {
                $ts = $f->last_opened_at ?? $f->created_at;
                return [
                    'type' => 'file',
                    'id' => $f->id,
                    'name' => $f->display_name,
                    'mime_type' => $f->mime_type,
                    'file_size' => (int) $f->file_size,
                    'timestamp' => $ts?->getTimestamp(),
                    'last_opened_at' => $f->last_opened_at?->toISOString(),
                ];
            });

        $folders = Folder::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->select(['id', 'folder_name', 'created_at'])
            ->get()
            ->map(function (Folder $d) {
                return [
                    'type' => 'folder',
                    'id' => $d->id,
                    'name' => $d->folder_name,
                    'timestamp' => $d->created_at?->getTimestamp(),
                    'created_at' => $d->created_at?->toISOString(),
                ];
            });

        /** @var Collection<int, array> $combined */
        $combined = $files->concat($folders)
            ->filter(fn($item) => $item['timestamp'] !== null)
            ->sortByDesc(fn($item) => $item['timestamp'])
            ->values()
            ->take($limit)
            ->map(function (array $item) {
                // Strip internal timestamp field
                unset($item['timestamp']);
                return $item;
            });

        return [
            'data' => $combined->all(),
        ];
    }

    public function getStats(User $user): array
    {
        $fileTypeStats = File::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->select([
                DB::raw('COALESCE(file_extension, \'\') as extension'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(file_size) as total_size'),
            ])
            ->groupBy('extension')
            ->orderByDesc('count')
            ->get()
            ->map(fn($row) => [
                'extension' => $row->extension,
                'count' => (int) $row->count,
                'total_size' => (int) $row->total_size,
            ])
            ->all();

        $days = 30;
        $end = CarbonImmutable::now()->startOfDay();
        $start = $end->subDays($days - 1);

        $rawTimeline = FileVersion::where('user_id', $user->id)
            ->whereBetween('created_at', [$start, $end->endOfDay()])
            ->select([
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(file_size) as uploaded'),
            ])
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get()
            ->keyBy('date');

        $storageTimeline = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->addDays($i)->format('Y-m-d');
            $uploaded = (int) ($rawTimeline[$date]->uploaded ?? 0);
            $storageTimeline[] = [
                'date' => $date,
                'uploaded' => $uploaded,
            ];
        }

        $totalFiles = File::where('user_id', $user->id)
            ->where('is_deleted', false)
            ->count();

        $totalStorageUsed = (int) ($user->storage_used ?? 0);

        return [
            'file_type_stats' => $fileTypeStats,
            'storage_timeline' => $storageTimeline,
            'total_storage_used' => $totalStorageUsed,
            'total_files' => (int) $totalFiles,
        ];
    }
}
