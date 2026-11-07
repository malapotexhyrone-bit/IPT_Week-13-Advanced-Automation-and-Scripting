<?php

use App\Http\Controllers\Api\V1\AutomationFailureController;
use App\Http\Controllers\Api\V1\ClearanceController;
use App\Http\Controllers\Api\V1\CurrentUserController;
use App\Http\Controllers\Api\V1\DeficiencyResolutionController;
use App\Http\Controllers\Api\V1\DemoSessionController;
use App\Http\Controllers\Api\V1\OfficeTaskQueueController;
use App\Http\Controllers\Api\V1\OfficeTaskReviewController;
use App\Http\Controllers\Api\V1\SecureExchangeController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::post('/demo/session', [DemoSessionController::class, 'store']);
    Route::post('/secure-exchange/receive', [SecureExchangeController::class, 'receive'])
        ->middleware('exchange.auth');
});

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::get('/me', [CurrentUserController::class, 'show']);
    Route::post('/clearances', [ClearanceController::class, 'store'])
        ->middleware('role:'.User::ROLE_STUDENT);
    Route::get('/clearances/current', [ClearanceController::class, 'current'])
        ->middleware('role:'.User::ROLE_STUDENT);

    Route::post('/office-tasks/{officeTask}/deficiencies', [OfficeTaskReviewController::class, 'storeDeficiency'])
        ->middleware('role:'.User::ROLE_OFFICE);
    Route::post('/office-tasks/{officeTask}/approve', [OfficeTaskReviewController::class, 'approve'])
        ->middleware('role:'.User::ROLE_OFFICE);
    Route::get('/office-tasks', [OfficeTaskQueueController::class, 'index'])
        ->middleware('role:'.User::ROLE_OFFICE);
    Route::post('/deficiencies/{deficiency}/resolution', [DeficiencyResolutionController::class, 'store'])
        ->middleware('role:'.User::ROLE_STUDENT);

    Route::get('/automation/failures', [AutomationFailureController::class, 'index'])
        ->middleware('role:'.User::ROLE_ADMIN);
    Route::post('/automation/deliveries/{automationDelivery}/retry', [AutomationFailureController::class, 'retry'])
        ->middleware('role:'.User::ROLE_ADMIN);
    Route::post('/secure-exchange/send', [SecureExchangeController::class, 'send'])
        ->middleware('role:'.User::ROLE_ADMIN);
});
