<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Middleware\SellerMiddleware;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CustomerMiddleware;
use App\Models\Transaction;
use Illuminate\Foundation\Configuration\Middleware;


Route::post('/otp/send', [OtpController::class, 'send']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::post('/register', [UserController::class, 'register']);

Route::post('/login', [UserController::class, 'login']);
//
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::middleware("auth:sanctum")->group(function () {

    Route::post('changePassword', [ProfileController::class, 'changePassword']);

    Route::post('/forgotPassword', [UserController::class, 'forgotPassword']);
    Route::post('/changePassword', [ProfileController::class, 'changePassword']);
//
    Route::post('updateProfile', [ProfileController::class, 'update']);

    //* Admin
    Route::middleware("role:admin")->group(function () {

        Route::get('/showUsers', [AdminController::class, 'showUsers']);

        Route::post('/blockUser', [AdminController::class, 'blockUser']);


        Route::post('/unBlockUser/{id}', [AdminController::class, 'unBlockUser']);
        Route::get('/getBlockedUsers', [AdminController::class, 'getBlockedUsers']);
        //
        Route::get('/checkIfUserBlocked/{id}', [AdminController::class, 'checkIfUserBlocked']);
         //
        Route::get('getTransactions', [AdminController::class, 'getTransactions']);
        Route::post('handleDepositTransaction/{id}', [AdminController::class, 'handleDepositTransaction']);

    });


    Route::get('/showallproducts', [ProductController::class, 'showAllProducts']);

    Route::get('/products/{category_id}/categories', [ProductController::class, 'getProductByCategory']);

    Route::get('/products/searchByProductUrl', [ProductController::class, 'searchProductsByProductUrl']);

    Route::get('/products/filter', [ProductController::class, 'filterProducts']);

    //* Seller
    Route::middleware("role:seller")->group(function () {
        Route::post('/products', [SellerController::class, 'createProduct']);
        Route::put('/products/{id}', [SellerController::class, 'updateProduct']);
        Route::delete('/products/{id}', [SellerController::class, 'deleteProduct']);
        Route::put('/product/{id}/hide', [SellerController::class, 'hideProduct']);
        Route::put('/product/{id}/show', [SellerController::class, 'activeProduct']);
        Route::get('/getAllMyProducts', [SellerController::class, 'getAllMyProducts']);
        Route::get('/getMyInactiveProducts', [SellerController::class, 'getMyInactiveProducts']);
        Route::get('/getMyActiveProducts', [SellerController::class, 'getMyActiveProducts']);
        Route::get('/countMyActiveProducts', [SellerController::class, 'countMyActiveProducts']);
        Route::get('/countMyInactiveProducts', [SellerController::class, 'countMyInactiveProducts']);
    });

    //* Customer
    Route::middleware("role:customer")->group(function () {
        Route::post('/deposit', [TransactionController::class, 'deposit']);
        Route::post('/addToFavorites/{id}', [CustomerController::class, 'addToFavorites']);
        Route::post('/removeFromFavorites/{id}', [CustomerController::class, 'removeFromFavorites']);
        Route::get('/getFavoriteProducts', [CustomerController::class, 'getFavoriteTasks']);
  
     });


    //* Seller, Customer
    Route::middleware('role:customer,seller')->group(function () {

        Route::post('/changePin', [WalletController::class, 'changePin']);


    });


});





