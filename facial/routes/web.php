<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\SuperAdmin\v1\OrganisationController;
use App\Http\Controllers\SuperAdmin\v1\StreamController;
use App\Http\Controllers\SuperAdmin\v1\UserController;
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
Route::middleware(['auth', 'role:super_admin'])->prefix('admin')->group(function () {
    // Users
    Route::get('/users', [UserController::class, 'listUser'])->name('superadmin.users.index');
    Route::get('/users/{uuid}', [UserController::class, 'getUser'])
        ->whereUuid('uuid')->name('superadmin.users.show');

    // Organisations
    Route::get('/organisations', [OrganisationController::class, 'listOrganisation'])
        ->name('superadmin.organisations.index');
    Route::get('/organisations/{uuid}', [OrganisationController::class, 'getOrganisation'])
        ->whereUuid('uuid')->name('superadmin.organisations.show');
    Route::match(['put', 'patch'], '/organisations/{uuid}', [OrganisationController::class, 'updateOrganisation'])
        ->whereUuid('uuid')->name('superadmin.organisations.update');
    Route::delete('/organisations/{uuid}', [OrganisationController::class, 'deleteOrganisation'])
        ->whereUuid('uuid')->name('superadmin.organisations.destroy');

    // Streams
    Route::get('/streams', [StreamController::class, 'listStream'])
        ->name('superadmin.streams.index');
    Route::post('/streams', [StreamController::class, 'createStream'])
        ->name('superadmin.streams.store');
    Route::get('/streams/{uuid}', [StreamController::class, 'getStream'])
        ->whereUuid('uuid')->name('superadmin.streams.show');
    Route::match(['put', 'patch'], '/streams/{uuid}', [StreamController::class, 'updateStream'])
        ->whereUuid('uuid')->name('superadmin.streams.update');
    Route::delete('/streams/{uuid}', [StreamController::class, 'deleteStream'])
        ->whereUuid('uuid')->name('superadmin.streams.destroy');
});