<?php

use App\Http\Controllers\Api\MobileAppController;
use Illuminate\Support\Facades\Route;

Route::middleware('mobile_app')->prefix('mobile')->group(function () {
    Route::post('/fcm-token', [MobileAppController::class, 'storeFcmToken']);
    Route::delete('/fcm-token', [MobileAppController::class, 'removeFcmToken']);
});
