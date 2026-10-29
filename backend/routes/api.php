<?php

use App\Http\Controllers\Api\V1\ClearanceController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function (): void {
    Route::post('/clearances', [ClearanceController::class, 'store'])
        ->middleware('role:'.User::ROLE_STUDENT);
});
