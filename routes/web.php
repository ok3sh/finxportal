<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/auth/login', [AuthController::class, 'login']);
Route::get('/auth/callback', [AuthController::class, 'callback']);
Route::get('/auth/status', [AuthController::class, 'status']);
Route::get('/auth/logout', [AuthController::class, 'logout']);
Route::get('/auth/calendar/refresh', [AuthController::class, 'refreshCalendar']);
Route::get('/api/documents', [DocumentController::class, 'index']);
