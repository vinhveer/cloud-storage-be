<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\PublicLink;
use Carbon\Carbon;

class AuthOrPublicLink
{
    /**
     * Handle an incoming request.
     * If a user is authenticated via Sanctum, allow.
     * Otherwise, if a public link token is provided and valid for the required permission, allow.
     * Defaults to 'view' permission.
     */
    public function handle(Request $request, Closure $next, string $permission = 'view')
    {
        // Already authenticated by previous layer
        if (Auth::check()) {
            return $next($request);
        }

        // Try to resolve user via sanctum guard (if Authorization header present)
        try {
            $user = Auth::guard('sanctum')->user();
            if ($user) {
                Auth::setUser($user);
                return $next($request);
            }
        } catch (\Throwable $e) {
            // ignore and fallthrough to token check
        }

        // Public token present?
        $token = $request->query('token') ?? $request->input('token');
        if ($token) {
            $pl = PublicLink::where('token', $token)->first();
            if (! $pl) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => 'Public link not found',
                        'code' => 'FORBIDDEN',
                        'errors' => null,
                    ],
                    'meta' => null,
                ], 403);
            }

            // revoked or expired?
            if ($pl->revoked_at !== null) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => 'Public link revoked',
                        'code' => 'FORBIDDEN',
                        'errors' => null,
                    ],
                    'meta' => null,
                ], 403);
            }

            if ($pl->expired_at !== null && Carbon::now()->greaterThan($pl->expired_at)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => 'Public link expired',
                        'code' => 'FORBIDDEN',
                        'errors' => null,
                    ],
                    'meta' => null,
                ], 403);
            }

            // permission mapping: download implies view as well
            $allowed = match ($permission) {
                'view' => ['view', 'download'],
                'download' => ['download'],
                default => ['view'],
            };

            if (! in_array($pl->permission, $allowed, true)) {
                return response()->json([
                    'success' => false,
                    'data' => null,
                    'error' => [
                        'message' => 'Public link does not grant required permission',
                        'code' => 'FORBIDDEN',
                        'errors' => null,
                    ],
                    'meta' => null,
                ], 403);
            }

            // Attach public link instance for downstream usage if needed
            $request->attributes->set('public_link', $pl);

            return $next($request);
        }

        // No auth and no public token
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => [
                'message' => 'Unauthenticated',
                'code' => 'UNAUTHENTICATED',
                'errors' => null,
            ],
            'meta' => null,
        ], 401);
    }
}
