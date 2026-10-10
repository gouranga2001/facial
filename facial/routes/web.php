<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;


Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);

Route::get('/organisation/setup', [AuthController::class, 'showSetup'])->name('setup');
Route::post('/organisation/setup', [AuthController::class, 'register'])->name('setup.store');

Route::get('/setup', [AuthController::class, 'showSetup'])->name('setup');
//admin routes
Route::middleware('auth:sanctum','role:super_admin' )->group(function () {
    Route::get('/admin/dashboard', function () {
        return view('adminDashboard');
    });
});