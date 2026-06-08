<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AdminMiddleware;
use Illuminate\Foundation\Configuration\Middleware;
Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');



Route::post('/otp/send', [OtpController::class, 'send']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [UserController::class, 'login']);

Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware("auth:sanctum")->group(function () {

    Route::post('changePassword', [ProfileController::class, 'changePassword']);

    Route::post('/forgotPassword', [UserController::class, 'forgotPassword']);
    Route::post('/changePassword', [ProfileController::class, 'changePassword']);



Route::middleware("admin")->group(function () {

    Route::get('/showUsers', [AdminController::class, 'showUsers']);

    Route::post('/blockUser', [AdminController::class, 'blockUser']);


        Route::post('/unBlockUser/{id}', [AdminController::class, 'unBlockUser']);
        Route::get('/getBlockedUsers', [AdminController::class, 'getBlockedUsers']);


    });


});

