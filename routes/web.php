<?php


use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return "You thought it will always be 404 !";
});

Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');






