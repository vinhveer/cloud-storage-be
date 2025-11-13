<?php

namespace App\Http\Controllers\Api\Search;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SearchSuggestionsController extends Controller
{
    /**
     * Return autocomplete suggestions for files and folders.
     * q is required.
     */
    public function suggestions(Request $request)
    {
        $q = $request->query('q');
        $type = $request->query('type', 'all');
        $limit = max(1, min(100, (int) $request->query('limit', 10)));

        if (! $q) {
            return response()->json(['error' => 'q parameter is required'], 400);
        }

        $like = '%' . strtolower($q) . '%';

        $suggestions = collect();

        if ($type === 'file' || $type === 'all') {
            $files = DB::table('files as f')
                ->where('f.is_deleted', 0)
                ->whereRaw('LOWER(f.display_name) LIKE ?', [$like])
                ->select('f.id', 'f.display_name', 'f.created_at')
                ->orderByDesc('f.created_at')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'file',
                        'id' => (int) $item->id,
                        'name' => $item->display_name,
                    ];
                });

            $suggestions = $suggestions->merge($files);
        }

        if ($type === 'folder' || $type === 'all') {
            $folders = DB::table('folders as fo')
                ->where('fo.is_deleted', 0)
                ->whereRaw('LOWER(fo.folder_name) LIKE ?', [$like])
                ->select('fo.id', 'fo.folder_name', 'fo.created_at')
                ->orderByDesc('fo.created_at')
                ->limit($limit)
                ->get()
                ->map(function ($item) {
                    return [
                        'type' => 'folder',
                        'id' => (int) $item->id,
                        'name' => $item->folder_name,
                    ];
                });

            $suggestions = $suggestions->merge($folders);
        }

        // ensure unique by name, preserve order, then limit to requested size
        $final = $suggestions->unique('name')->values()->slice(0, $limit)->values();

        return response()->json(['suggestions' => $final]);
    }
}
