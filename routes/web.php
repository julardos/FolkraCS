<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Landlord\ConversationController as LandlordConversationController;
use App\Http\Controllers\Landlord\LiveAgentController as LandlordLiveAgentController;
use App\Http\Controllers\Landlord\TenantController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureLandlord;
use Illuminate\Support\Facades\Route;

$landlordDomain = env('LANDLORD_DOMAIN', 'folkra.co');

Route::domain($landlordDomain)->group(function () {

    Route::get('/', fn() => auth()->check() ? redirect('/dashboard') : redirect('/login'));

    // Auth (guest routes: login, forgot-password, reset-password)
    require __DIR__ . '/auth.php';

    // Authenticated landlord routes
    Route::middleware(['auth', EnsureLandlord::class])->group(function () {

        // Unified dashboard — DashboardController checks role internally
        Route::get('/dashboard', DashboardController::class)->name('landlord.dashboard');

        Route::get('/conversations', [LandlordConversationController::class, 'index'])->name('landlord.conversations');
        Route::post('/customers/{customer}/takeover', [LandlordLiveAgentController::class, 'enable'])->name('landlord.takeover.enable');
        Route::delete('/customers/{customer}/takeover', [LandlordLiveAgentController::class, 'disable'])->name('landlord.takeover.disable');

        Route::get('/clients', [ClientController::class, 'index'])->name('landlord.clients');
        Route::post('/clients', [ClientController::class, 'store'])->name('landlord.clients.store');
        Route::put('/clients/{client}', [ClientController::class, 'update'])->name('landlord.clients.update');
        Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('landlord.clients.destroy');

        Route::get('/tenants', [TenantController::class, 'index'])->name('landlord.tenants');
        Route::post('/tenants', [TenantController::class, 'store'])->name('landlord.tenants.store');
        Route::delete('/tenants/{client}', [TenantController::class, 'destroy'])->name('landlord.tenants.destroy');

        // Send password reset to a tenant user (from landlord dashboard)
        Route::post('/tenants/users/{user}/reset-password', [TenantController::class, 'resetUserPassword'])->name('landlord.reset-user-password');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
});
