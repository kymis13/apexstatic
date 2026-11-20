<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'APEX STATS API',
        'version' => '1.0.0',
        'status' => 'ok',
        'docs' => '/api/health',
    ]);
});

Route::get('/healthz', function () {
    return response()->json([
        'status' => 'ok',
        'database' => rescue(fn () => \Illuminate\Support\Facades\DB::select('SELECT 1') ? 'ok' : 'error', 'error'),
        'time' => now()->toIso8601String(),
    ]);
});
