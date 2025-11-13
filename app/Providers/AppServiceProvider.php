<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use App\Models\User;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Define a simple 'admin' ability used by the routes middleware `can:admin`.
        // This guards admin routes and returns a proper 403 when the user is not admin.
        Gate::define('admin', function (?User $user) {
            if (! $user) {
                return false;
            }

            // Role may be stored as string like 'admin' or 'superadmin'.
            return isset($user->role) && in_array(strtolower($user->role), ['admin', 'superadmin'], true);
        });
    }
}
