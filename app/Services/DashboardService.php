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

    

    public function getStats(User $user, ?string $startDate = null, ?string $endDate = null): array
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

        // Determine timeline range based on provided params. If none provided,
        // span the available FileVersion data for the user.
        if ($startDate !== null || $endDate !== null) {
            $start = $startDate ? CarbonImmutable::parse($startDate)->startOfDay() : null;
            $end = $endDate ? CarbonImmutable::parse($endDate)->startOfDay() : null;

            if ($start === null && $end !== null) {
                $start = $end;
            }
            if ($end === null && $start !== null) {
                $end = $start;
            }

            if ($start === null || $end === null) {
                $storageTimeline = [];
            } else {
                if ($start->greaterThan($end)) {
                    [$start, $end] = [$end, $start];
                }

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

                $days = $start->diffInDays($end) + 1;
                $storageTimeline = [];
                for ($i = 0; $i < $days; $i++) {
                    $date = $start->addDays($i)->format('Y-m-d');
                    $uploaded = (int) ($rawTimeline[$date]->uploaded ?? 0);
                    $storageTimeline[] = [
                        'date' => $date,
                        'uploaded' => $uploaded,
                    ];
                }
            }
        } else {
            // No date params provided: span all available data for the user.
            $min = FileVersion::where('user_id', $user->id)->min('created_at');
            $max = FileVersion::where('user_id', $user->id)->max('created_at');

            if ($min === null || $max === null) {
                $storageTimeline = [];
            } else {
                $start = CarbonImmutable::parse($min)->startOfDay();
                $end = CarbonImmutable::parse($max)->startOfDay();

                if ($start->greaterThan($end)) {
                    [$start, $end] = [$end, $start];
                }

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

                $days = $start->diffInDays($end) + 1;
                $storageTimeline = [];
                for ($i = 0; $i < $days; $i++) {
                    $date = $start->addDays($i)->format('Y-m-d');
                    $uploaded = (int) ($rawTimeline[$date]->uploaded ?? 0);
                    $storageTimeline[] = [
                        'date' => $date,
                        'uploaded' => $uploaded,
                    ];
                }
            }
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
