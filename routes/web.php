<?php

declare(strict_types=1);

use Finget\Laravel\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/login', [LoginController::class, 'redirect'])->name('finget.login');
Route::get('/callback', [LoginController::class, 'callback'])->name('finget.callback');
