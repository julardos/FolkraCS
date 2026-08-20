<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Landlord\DashboardController as LandlordDashboard;
use App\Http\Controllers\Tenant\DashboardController as TenantDashboard;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        if ($user->role === 'landlord') {
            return app(LandlordDashboard::class)->index();
        }

        // Tenant admin — tenant() is initialized by the time we get here
        return app(TenantDashboard::class)->index();
    }
}
