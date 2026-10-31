<?php

use App\Http\Controllers\SuperAdmin\v1\UserController;
use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login');

// Auth
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// Organisation setup (/setup kept as an alias so old links still work)
Route::get('/organisation/setup', [AuthController::class, 'showSetup'])->name('setup');
Route::post('/organisation/setup', [AuthController::class, 'register'])->name('setup.store');
Route::redirect('/setup', '/organisation/setup');

// Super admin
Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/admin/users', [UserController::class, 'listUser'])->name('superadmin.users.index');
    Route::get('/admin/users/{uuid}', [UserController::class, 'getUser'])
        ->whereUuid('uuid')->name('superadmin.users.show');
});