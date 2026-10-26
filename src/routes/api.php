<?php

use App\Http\Controllers\Api\V1\BlueprintController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/blueprints', [BlueprintController::class, 'index']);
    Route::get('/blueprints/{id}', [BlueprintController::class, 'show']);
});
