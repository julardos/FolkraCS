<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Tenant\AiSettingsController;
use App\Http\Controllers\Tenant\ConversationController;
use App\Http\Controllers\Tenant\EscalationController;
use App\Http\Controllers\Tenant\KnowledgeBaseController;
use App\Http\Controllers\Tenant\LiveAgentController;
use App\Http\Controllers\Tenant\UserController;
use App\Http\Middleware\EnsureTenantAdmin;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    PreventAccessFromCentralDomains::class,
    InitializeTenancyByDomain::class,
])->group(function () {

    Route::get('/', fn() => redirect('/dashboard'));

    // Auth — all using plain paths (no Ziggy route() dependency on tenant domain)
    Route::middleware('guest')->group(function () {
        Route::get('/login',          [AuthenticatedSessionController::class, 'create'])->name('login');
        Route::post('/login',         [AuthenticatedSessionController::class, 'store']);
        Route::get('/forgot-password',[PasswordResetLinkController::class, 'create'])->name('password.request');
        Route::post('/forgot-password',[PasswordResetLinkController::class, 'store'])->name('password.email');
        Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
        Route::post('/reset-password',        [NewPasswordController::class, 'store'])->name('password.update');
    });
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Authenticated tenant routes
    Route::middleware(['auth'])->group(function () {

        Route::get('/dashboard', DashboardController::class)->name('tenant.dashboard');

        // Conversations (all admins can view)
        Route::get('/conversations', [ConversationController::class, 'index'])->name('tenant.conversations');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('tenant.conversations.show');
        Route::post('/customers/{customer}/takeover', [LiveAgentController::class, 'enable'])->name('tenant.takeover.enable');
        Route::delete('/customers/{customer}/takeover', [LiveAgentController::class, 'disable'])->name('tenant.takeover.disable');

        // Admin-only routes (configure the bot)
        Route::middleware([EnsureTenantAdmin::class])->group(function () {

            Route::get('/ai-settings', [AiSettingsController::class, 'index'])->name('tenant.ai-settings');
            Route::put('/ai-settings', [AiSettingsController::class, 'update'])->name('tenant.ai-settings.update');

            Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('tenant.knowledge-base');
            Route::post('/knowledge-base', [KnowledgeBaseController::class, 'store'])->name('tenant.knowledge-base.store');
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
