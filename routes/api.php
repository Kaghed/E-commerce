<?php

use App\Http\Controllers\OtpController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::post('/otp/send', [OtpController::class, 'send']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [UserController::class, 'login']);

Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

