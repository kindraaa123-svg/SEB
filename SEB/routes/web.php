<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\CodeRedemptionController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::post('/code/redeem', [CodeRedemptionController::class, 'redeem'])->name('code.redeem');

Route::middleware('supervisor.auth')->group(function () {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::get('/login-code', [HomeController::class, 'loginCode'])->name('code.login');
    Route::get('/unlock-code', [HomeController::class, 'unlockCode'])->name('code.unlock');
    Route::get('/exit-code', [HomeController::class, 'exitCode'])->name('code.exit');
    Route::get('/activity', [HomeController::class, 'activity'])->name('activity');
    Route::get('/account', [HomeController::class, 'account'])->name('account');
    Route::post('/account', [HomeController::class, 'updateAccount'])->name('account.update');
    Route::post('/account/password', [HomeController::class, 'updatePassword'])->name('account.password.update');
    Route::post('/generate-code', [HomeController::class, 'generate'])->name('code.generate');
});
