<?php

namespace App\Providers;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class TenantRouteServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        // No outer middleware — tenant.php applies 'web' internally.
        // Applying 'web' here too causes VerifyCsrfToken to run twice,
        // which silently kills POST requests with 419 (Inertia reloads login, no error shown).
        Route::group([], base_path('routes/tenant.php'));
    }
}
