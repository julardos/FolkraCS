<?php

use Illuminate\Support\Facades\Route;
use Modules\WhatsApp\Http\Controllers\WhatsAppController;

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('whatsapps', WhatsAppController::class)->names('whatsapp');
});
