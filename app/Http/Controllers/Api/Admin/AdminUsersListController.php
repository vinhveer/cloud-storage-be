<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminUsersListController extends BaseApiController
{
    /**
     * GET /api/admin/users - list all users with optional search and pagination
     */
    public function index(Request $request)
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // Basic check for admin role; route is also behind admin middleware, but double-check
        if (! isset($user->role) || $user->role !== 'admin') {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        $search = trim((string) $request->query('search', ''));
        $page = max(1, (int) $request->query('page', 1));
        $perPage = max(1, min(100, (int) $request->query('per_page', 15)));
        $offset = ($page - 1) * $perPage;

        $baseQB = DB::table('users')->select('id', 'name', 'email', 'role', 'storage_limit', 'storage_used');

        if ($search !== '') {
            $like = '%' . str_replace('%', '\\%', $search) . '%';
            $baseQB->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('email', 'like', $like);
            });
        }

        $total = (int) DB::table(DB::raw("({$baseQB->toSql()}) as t"))
            ->mergeBindings($baseQB)
            ->count();

        $rows = $baseQB->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($perPage)
            ->get();

        $data = $rows->map(function ($r) {
            return [
                'user_id' => (int) $r->id,
                'name' => $r->name,
                'email' => $r->email,
                'role' => $r->role,
                'storage_limit' => $r->storage_limit !== null ? (int) $r->storage_limit : null,
                'storage_used' => $r->storage_used !== null ? (int) $r->storage_used : null,
            ];
        })->values();

        $totalPages = (int) ceil($total / $perPage);

        return response()->json([
            'data' => $data,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_items' => $total,
            ],
        ]);
    }
}
