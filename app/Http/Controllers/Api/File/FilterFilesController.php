<?php

namespace App\Http\Controllers\Api\File;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class FilterFilesController extends Controller
{
    /**
     * GET /api/files with filters and sort for current user.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $filters = $request->query('filter', []);
        $extension = $filters['extension'] ?? null;
        $sizeMin = isset($filters['size_min']) ? (int) $filters['size_min'] : null;
        $sizeMax = isset($filters['size_max']) ? (int) $filters['size_max'] : null;
        $dateFrom = $filters['date_from'] ?? null;
        $dateTo = $filters['date_to'] ?? null;
        $filterShared = isset($filters['shared']) ? filter_var($filters['shared'], FILTER_VALIDATE_BOOLEAN) : null;

        $sort = $request->query('sort'); // e.g. name,asc or size,desc
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 20)));
        $offset = ($page - 1) * $perPage;

        // Base query: select file fields + subquery for first version created_at + detect shared
        $fileQB = DB::table('files as f')
            ->leftJoin('shares as s', function ($join) {
                $join->on('s.file_id', '=', 'f.id')->where('s.shareable_type', 'file');
            })
            ->leftJoin('public_links as pl', function ($join) {
                $join->on('pl.file_id', '=', 'f.id')->where('pl.shareable_type', 'file');
            })
            ->where('f.is_deleted', 0)
            ->where('f.user_id', $user->id)
            ->selectRaw(
                "f.id, f.display_name, f.file_extension, f.file_size, f.mime_type, f.last_opened_at, (SELECT created_at FROM file_versions WHERE file_versions.file_id = f.id ORDER BY created_at ASC LIMIT 1) as created_at, CASE WHEN pl.id IS NOT NULL OR s.id IS NOT NULL THEN 1 ELSE 0 END as shared"
            );

        if ($extension) {
            $fileQB->whereRaw('LOWER(f.file_extension) = ?', [strtolower($extension)]);
        }

        if ($sizeMin !== null) {
            $fileQB->where('f.file_size', '>=', $sizeMin);
        }
        if ($sizeMax !== null) {
            $fileQB->where('f.file_size', '<=', $sizeMax);
        }

        if ($dateFrom) {
            $fileQB->whereRaw("(SELECT created_at FROM file_versions WHERE file_versions.file_id = f.id ORDER BY created_at ASC LIMIT 1) >= ?", [$dateFrom]);
        }
        if ($dateTo) {
            $fileQB->whereRaw("(SELECT created_at FROM file_versions WHERE file_versions.file_id = f.id ORDER BY created_at ASC LIMIT 1) <= ?", [$dateTo]);
        }

        if ($filterShared !== null) {
            if ($filterShared) {
                $fileQB->where(function ($w) {
                    $w->whereNotNull('pl.id')->orWhereNotNull('s.id');
                });
            } else {
                $fileQB->whereNull('pl.id')->whereNull('s.id');
            }
        }

        // Sorting
        if ($sort) {
            $parts = explode(',', $sort);
            $field = $parts[0] ?? 'created_at';
            $dir = strtolower($parts[1] ?? 'desc') === 'asc' ? 'asc' : 'desc';

            switch ($field) {
                case 'name':
                    $fileQB->orderBy('f.display_name', $dir);
                    break;
                case 'size':
                    $fileQB->orderBy('f.file_size', $dir);
                    break;
                case 'last_opened_at':
                    $fileQB->orderBy('f.last_opened_at', $dir);
                    break;
                case 'created_at':
                default:
                    // created_at is a subquery alias; use orderByRaw
                    $fileQB->orderByRaw("created_at {$dir}");
                    break;
            }
        } else {
            $fileQB->orderByRaw('created_at desc');
        }

        // total (distinct) count
        $total = (clone $fileQB)->distinct()->count('f.id');

        $items = $fileQB->distinct()->offset($offset)->limit($perPage)->get();

        $formatted = $items->map(function ($item) {
            return [
                'file_id' => (int) $item->id,
                'display_name' => $item->display_name,
                'file_extension' => $item->file_extension,
                'file_size' => (int) $item->file_size,
                'mime_type' => $item->mime_type,
                'shared' => (bool) $item->shared,
                'last_opened_at' => $item->last_opened_at,
                'created_at' => $item->created_at,
            ];
        });

        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'data' => $formatted,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }
}
