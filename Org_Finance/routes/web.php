<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Mycontroller;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IcsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');

// Handle submission
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Log out (used by the Log Out button in the dashboard menu)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Pages that need a login (people who are not logged in are sent to the login page)
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/info_manage', function () {
        return view('info_manage');
    })->name('info_manage');

    // ICS database routes (used by public/js/dashboard.js)
    Route::prefix('ics')->group(function () {
        Route::get('data', [IcsController::class, 'data']);
        Route::post('records', [IcsController::class, 'storeRecord']);
        Route::put('records/{record}', [IcsController::class, 'updateRecord']);
        Route::delete('records/{record}', [IcsController::class, 'destroyRecord']);
        Route::post('users', [IcsController::class, 'storeUser']);
        Route::put('users/{user}', [IcsController::class, 'updateUser']);
        Route::delete('users/{user}', [IcsController::class, 'destroyUser']);
        Route::post('logs', [IcsController::class, 'storeLog']);
    });
});