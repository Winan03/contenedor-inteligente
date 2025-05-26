<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// ✅ Estas rutas deben existir en este archivo
Route::get('/contenedores', [DashboardController::class, 'apiContenedores']);
Route::get('/contenedor/{id}', [DashboardController::class, 'apiContenedor']);
Route::get('/estadisticas', [DashboardController::class, 'apiEstadisticas']);
Route::get('/health', [DashboardController::class, 'health']);
Route::get('/stream', [DashboardController::class, 'stream']);
