<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsApp\Http\Controllers\WebhookController;

Route::post('/webhook', [WebhookController::class, 'receive'])
    ->middleware('throttle:120,1')
    ->name('whatsapp.webhook');
