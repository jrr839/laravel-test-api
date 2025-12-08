<?php

use App\Http\Controllers\Api\Auth\LoginController;
use App\Http\Controllers\Api\Auth\LogoutController;
use App\Http\Controllers\Api\Auth\PasswordResetController;
use App\Http\Controllers\Api\Auth\RegisterController;
use App\Http\Controllers\Api\Auth\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/auth/register', [RegisterController::class, 'store']);
Route::post('/auth/login', [LoginController::class, 'store']);
Route::get('/auth/user', [UserController::class, 'show'])->middleware('auth:sanctum');
Route::post('/auth/logout', [LogoutController::class, 'destroy'])->middleware('auth:sanctum');
Route::delete('/auth/logout/all', [LogoutController::class, 'destroyAll'])->middleware('auth:sanctum');
Route::post('/auth/forgot-password', [PasswordResetController::class, 'sendResetLink']);
Route::post('/auth/reset-password', [PasswordResetController::class, 'reset']);

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
