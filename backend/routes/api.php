<?php

use App\Http\Controllers\Api\Admin\AdminDashboardController;
use App\Http\Controllers\Api\Admin\AdminLegendController;
use App\Http\Controllers\Api\Admin\AdminRequestController;
use App\Http\Controllers\Api\Admin\AdminUserController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\LeaderboardController;
use App\Http\Controllers\Api\LegendController;
use App\Http\Controllers\Api\StatsController;
use App\Http\Controllers\Api\SteamController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public API
|--------------------------------------------------------------------------
*/
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => rescue(fn () => \Illuminate\Support\Facades\DB::select('SELECT 1') ? 'ok' : 'error', 'error'),
        'time' => now()->toIso8601String(),
    ]);
});

Route::get('/legends', [LegendController::class, 'index']);
Route::get('/legends/{legend:slug}', [LegendController::class, 'show']);
Route::get('/leaderboard', [LeaderboardController::class, 'index']);

// Вход только через Steam OpenID. Обычной регистрации и пароля нет.
Route::get('/steam/redirect', [SteamController::class, 'redirect'])->middleware('throttle:steam');
Route::get('/steam/callback', [SteamController::class, 'callback'])->name('steam.callback')->middleware('throttle:steam');
Route::post('/auth/steam/exchange', [SteamController::class, 'exchange'])->middleware('throttle:steam');

/*
|--------------------------------------------------------------------------
| Authenticated API
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/auth/me', [AuthController::class, 'me']);
    Route::post('/auth/logout', [AuthController::class, 'logout']);

    Route::get('/me/overview', [StatsController::class, 'overview']);
    Route::get('/me/legends', [StatsController::class, 'legends']);
    Route::get('/me/matches', [StatsController::class, 'matches']);
    Route::post('/me/sync', [StatsController::class, 'sync'])->middleware('throttle:sync');

    Route::post('/verification-requests', [StatsController::class, 'storeVerificationRequest']);

    Route::delete('/steam/unlink', [SteamController::class, 'unlink']);

    /*
    |----------------------------------------------------------------------
    | Admin API — только роль admin
    |----------------------------------------------------------------------
    */
    Route::prefix('admin')->middleware('admin')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index']);

        Route::get('/users', [AdminUserController::class, 'index']);
        Route::patch('/users/{user}', [AdminUserController::class, 'update']);

        Route::get('/legends', [AdminLegendController::class, 'index']);
        Route::post('/legends', [AdminLegendController::class, 'store']);
        Route::patch('/legends/{legend}', [AdminLegendController::class, 'update']);
        Route::delete('/legends/{legend}', [AdminLegendController::class, 'destroy']);

        Route::get('/requests', [AdminRequestController::class, 'index']);
        Route::patch('/requests/{verificationRequest}', [AdminRequestController::class, 'update']);

        Route::get('/audit-logs', [AdminDashboardController::class, 'auditLogs']);
    });
});
