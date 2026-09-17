<?php

use App\Http\Controllers\MonitorController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthenticationController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('login', [AuthenticationController::class, 'Login']);
        Route::post('register', [AuthenticationController::class, 'Register']);
        Route::middleware(['auth:sanctum'])->get('/user', [AuthenticationController::class, 'GetUser']);
    });

    Route::middleware(['auth:sanctum'])->group(function () {
        Route::prefix('teams')->group(function () {
            Route::get('/', [TeamController::class, 'index']);
            Route::get('/{team}', [TeamController::class, 'show']);
            Route::post('/', [TeamController::class, 'store']);
            Route::put('/{team}', [TeamController::class, 'update']);
            Route::delete('/{team}', [TeamController::class, 'destroy']);
        });

        Route::prefix('projects')->group(function () {
            Route::get('/', [ProjectController::class, 'index']);
            Route::get('/{project}', [ProjectController::class, 'show']);
            Route::get('/{project}/monitors', [MonitorController::class, 'projects_monitors']);
            Route::post('/{project}/monitors', [MonitorController::class, 'store']);
            Route::post('/', [ProjectController::class, 'store']);
            Route::put('/{project}', [ProjectController::class, 'update']);
            Route::delete('/{project}', [ProjectController::class, 'destroy']);
        });

        Route::prefix('monitors')->group(function () {
            Route::get('/', [MonitorController::class, 'index']);
            Route::get('/{monitor}', [MonitorController::class, 'show']);
            Route::put('/{monitor}', [MonitorController::class, 'update']);
            Route::post('/{monitor}/enable', [MonitorController::class, 'enable_monitor']);
            Route::post('/{monitor}/disable', [MonitorController::class, 'disable_monitor']);
            Route::post('/{monitor}/run', [MonitorController::class, 'manual_run_monitor']);
            Route::delete('/{monitor}', [MonitorController::class, 'destroy']);
        });
    });
});
