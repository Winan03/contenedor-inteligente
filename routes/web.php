<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

Route::get('/', [DashboardController::class, 'index']);
Route::get('/contenedor/{id}', [DashboardController::class, 'show']);
Route::get('/estadisticas', [DashboardController::class, 'estadisticas']);
Route::get('/health-check', [DashboardController::class, 'health']);
