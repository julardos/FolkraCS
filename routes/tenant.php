<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Tenant\AiSettingsController;
use App\Http\Controllers\Tenant\ConnectionsController;
use App\Http\Controllers\Tenant\ConversationController;
use App\Http\Controllers\Tenant\EscalationController;
use App\Http\Controllers\Tenant\KnowledgeBaseController;
use App\Http\Controllers\Tenant\LiveAgentController;
use App\Http\Controllers\Tenant\OnboardingController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Middleware\EnsureTenantAdmin;
use App\Http\Middleware\LogTenantInit;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    LogTenantInit::class,
    PreventAccessFromCentralDomains::class,
    InitializeTenancyByDomain::class,
])->group(function () {

    Route::get('/', fn() => redirect('/dashboard'));

    Route::middleware('guest')->group(function () {
        Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('tenant.login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('tenant.login.store');
    });

    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('tenant.logout');

    // Authenticated tenant routes
    Route::middleware(['auth'])->group(function () {

        Route::get('/dashboard', DashboardController::class)->name('tenant.dashboard');

        // Conversations (all admins can view)
        Route::get('/conversations', [ConversationController::class, 'index'])->name('tenant.conversations');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('tenant.conversations.show');
        Route::post('/customers/{customer}/takeover', [LiveAgentController::class, 'enable'])->name('tenant.takeover.enable');
        Route::delete('/customers/{customer}/takeover', [LiveAgentController::class, 'disable'])->name('tenant.takeover.disable');

        // Onboarding (admin only)
        Route::middleware([EnsureTenantAdmin::class])->group(function () {
            Route::get('/onboarding', [OnboardingController::class, 'index'])->name('tenant.onboarding');
        });

        // Connections — WA QR proxy + Instagram OAuth (admin only)
        Route::middleware([EnsureTenantAdmin::class])->group(function () {
            Route::get('/connections', [ConnectionsController::class, 'index'])->name('tenant.connections');
            Route::get('/connections/wa/status', [ConnectionsController::class, 'waStatus'])->name('tenant.connections.wa-status');
            Route::get('/connections/wa/qr', [ConnectionsController::class, 'waQr'])->name('tenant.connections.wa-qr');
            Route::post('/connections/wa/start', [ConnectionsController::class, 'waStart'])->name('tenant.connections.wa-start');
            Route::post('/connections/wa/restart', [ConnectionsController::class, 'waRestart'])->name('tenant.connections.wa-restart');
            Route::get('/connections/instagram', [ConnectionsController::class, 'instagramConnect'])->name('tenant.connections.instagram');
            Route::delete('/connections/instagram', [ConnectionsController::class, 'instagramDisconnect'])->name('tenant.connections.instagram-disconnect');
        });

        // Admin-only routes (configure the bot)
        Route::middleware([EnsureTenantAdmin::class])->group(function () {

            Route::get('/ai-settings', [AiSettingsController::class, 'index'])->name('tenant.ai-settings');
            Route::put('/ai-settings', [AiSettingsController::class, 'update'])->name('tenant.ai-settings.update');

            Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('tenant.knowledge-base');
            Route::post('/knowledge-base', [KnowledgeBaseController::class, 'store'])->name('tenant.knowledge-base.store');
            Route::post('/knowledge-base/upload', [KnowledgeBaseController::class, 'store'])->name('tenant.knowledge-base.upload');
            Route::put('/knowledge-base/{kb}', [KnowledgeBaseController::class, 'update'])->name('tenant.knowledge-base.update');
            Route::delete('/knowledge-base/{kb}', [KnowledgeBaseController::class, 'destroy'])->name('tenant.knowledge-base.destroy');

            Route::get('/escalation', [EscalationController::class, 'index'])->name('tenant.escalation');
            Route::put('/escalation', [EscalationController::class, 'update'])->name('tenant.escalation.update');

            // User management within tenant
            Route::get('/users', [UserController::class, 'index'])->name('tenant.users');
            Route::post('/users', [UserController::class, 'store'])->name('tenant.users.store');
            Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('tenant.users.destroy');
        });
    });
});
