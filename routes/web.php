<?php

use App\Http\Controllers\ClientController;
use App\Http\Controllers\Landlord\ConversationController as LandlordConversationController;
use App\Http\Controllers\Landlord\DashboardController as LandlordDashboardController;
use App\Http\Controllers\Landlord\LiveAgentController as LandlordLiveAgentController;
use App\Http\Controllers\Landlord\TenantController;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureLandlordDomain;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Central / Landlord Routes
| Served on: landlord.localhost, landlord.folkra-cs.test
|--------------------------------------------------------------------------
*/

// Root → dashboard if logged in, otherwise login
Route::get('/', fn() => auth()->check() ? redirect('/dashboard') : redirect('/login'));

// Auth routes (login/logout — shared, works on any domain)
require __DIR__.'/auth.php';

// Landlord-only routes
Route::middleware(['auth', EnsureLandlordDomain::class])->group(function () {

    // Dashboard
    Route::get('/dashboard', [LandlordDashboardController::class, 'index'])->name('landlord.dashboard');

    // Conversations (all clients)
    Route::get('/conversations', [LandlordConversationController::class, 'index'])->name('landlord.conversations');
    Route::post('/customers/{customer}/takeover', [LandlordLiveAgentController::class, 'enable'])->name('landlord.takeover.enable');
    Route::delete('/customers/{customer}/takeover', [LandlordLiveAgentController::class, 'disable'])->name('landlord.takeover.disable');

    // Client management
    Route::get('/clients', [ClientController::class, 'index'])->name('landlord.clients');
    Route::post('/clients', [ClientController::class, 'store'])->name('landlord.clients.store');
    Route::put('/clients/{client}', [ClientController::class, 'update'])->name('landlord.clients.update');
    Route::delete('/clients/{client}', [ClientController::class, 'destroy'])->name('landlord.clients.destroy');

    // Tenant management (create tenant + user from client form)
    Route::get('/tenants', [TenantController::class, 'index'])->name('landlord.tenants');
    Route::post('/tenants', [TenantController::class, 'store'])->name('landlord.tenants.store');
    Route::delete('/tenants/{client}', [TenantController::class, 'destroy'])->name('landlord.tenants.destroy');

    // Profile
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
