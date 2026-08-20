<?php

declare(strict_types=1);

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Tenant\AiSettingsController;
use App\Http\Controllers\Tenant\ConversationController;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\EscalationController;
use App\Http\Controllers\Tenant\KnowledgeBaseController;
use App\Http\Controllers\Tenant\LiveAgentController;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {

    Route::get('/', fn() => redirect('/dashboard'));

    // Auth (login/logout on tenant domain)
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store']);
    Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Tenant dashboard (auth required)
    Route::middleware(['auth'])->group(function () {

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('tenant.dashboard');

        // AI & Prompt
        Route::get('/ai-settings', [AiSettingsController::class, 'index'])->name('tenant.ai-settings');
        Route::put('/ai-settings', [AiSettingsController::class, 'update'])->name('tenant.ai-settings.update');

        // Knowledge Base
        Route::get('/knowledge-base', [KnowledgeBaseController::class, 'index'])->name('tenant.knowledge-base');
        Route::post('/knowledge-base', [KnowledgeBaseController::class, 'store'])->name('tenant.knowledge-base.store');
        Route::put('/knowledge-base/{kb}', [KnowledgeBaseController::class, 'update'])->name('tenant.knowledge-base.update');
        Route::delete('/knowledge-base/{kb}', [KnowledgeBaseController::class, 'destroy'])->name('tenant.knowledge-base.destroy');

        // Escalation settings
        Route::get('/escalation', [EscalationController::class, 'index'])->name('tenant.escalation');
        Route::put('/escalation', [EscalationController::class, 'update'])->name('tenant.escalation.update');

        // Conversations
        Route::get('/conversations', [ConversationController::class, 'index'])->name('tenant.conversations');
        Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('tenant.conversations.show');

        // Live agent takeover
        Route::post('/customers/{customer}/takeover', [LiveAgentController::class, 'enable'])->name('tenant.takeover.enable');
        Route::delete('/customers/{customer}/takeover', [LiveAgentController::class, 'disable'])->name('tenant.takeover.disable');
    });
});
