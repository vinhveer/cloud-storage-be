<?php

namespace App\Http\Controllers\Api\Storage;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StorageBreakdownController extends Controller
{
    /**
     * GET /api/storage/breakdown
     * Group by file_extension by default, or by mime_type when ?by=mime
     * Response (exact shape): { success: true, user_id: int, breakdown: [ { type, total_size, count } ], total_size }
     */
    public function breakdown(Request $request)
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $by = $request->query('by', 'extension');

        if ($by === 'mime') {
            $groupExpr = "COALESCE(NULLIF(mime_type, ''), 'others')";
            $label = 'mime_type';
        } else {
            // normalize extension to lowercase and empty -> others
            $groupExpr = "COALESCE(NULLIF(LOWER(file_extension), ''), 'others')";
            $label = 'file_extension';
        }

        $rows = DB::table('files')
            ->selectRaw("{$groupExpr} as type, SUM(file_size) as total_size, COUNT(id) as count")
            ->where('user_id', $user->id)
            ->where('is_deleted', 0)
            ->groupBy('type')
            ->orderByDesc('total_size')
            ->get()
            ->map(function ($r) {
                return [
                    'type' => $r->type,
                    'total_size' => (int) $r->total_size,
                    'count' => (int) $r->count,
                ];
            })
            ->values()
            ->all();

        $total = array_sum(array_map(function ($g) { return $g['total_size']; }, $rows));

        return response()->json([
            'success' => true,
            'user_id' => $user->id,
            'breakdown' => $rows,
            'total_size' => (int) $total,
        ]);
    }
}
