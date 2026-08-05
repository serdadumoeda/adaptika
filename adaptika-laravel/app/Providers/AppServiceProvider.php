<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

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
        if (isset($_SERVER['VERCEL']) || isset($_ENV['VERCEL']) || getenv('VERCEL') === '1' || request()->header('x-forwarded-proto') === 'https') {
            \Illuminate\Support\Facades\URL::forceScheme('https');
        }

        Gate::define('is-superadmin', function (User $user) {
            return $user->role === 'Superadmin';
        });

        Gate::define('access-penyelenggara', function (User $user) {
            return in_array($user->role, ['Superadmin', 'Penyelenggara', 'Kepala Balai']);
        });

        Gate::define('write-penyelenggara', function (User $user) {
            return in_array($user->role, ['Superadmin', 'Penyelenggara']);
        });

        Gate::define('access-instruktur', function (User $user) {
            return in_array($user->role, ['Superadmin', 'Instruktur Teknis']);
        });

        Gate::define('access-pengantar', function (User $user) {
            return in_array($user->role, ['Superadmin', 'Pengantar Kerja']);
        });

        Gate::define('access-pemberdayaan', function (User $user) {
            return in_array($user->role, ['Superadmin', 'Seksi Pemberdayaan']);
        });
    }
}
