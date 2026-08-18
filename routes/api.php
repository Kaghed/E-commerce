<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdvertismentController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Middleware\SellerMiddleware;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\RatingController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SupportRequestController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CustomerMiddleware;
use App\Models\Transaction;
use Illuminate\Foundation\Configuration\Middleware;


Route::post('/otp/send', [OtpController::class, 'send']);
Route::post('/otp/verify', [OtpController::class, 'verify']);

Route::post('/register', [UserController::class, 'register']);
Route::post('/verifyRegister', [UserController::class, 'verifyRegister']);

Route::post('/login', [UserController::class, 'login']);
//
Route::post('/logout', [UserController::class, 'logout'])->middleware('auth:sanctum');

Route::post('/forgotPassword', [UserController::class, 'forgotPassword']);

Route::middleware("auth:sanctum")->group(function () {


    Route::post('/changePassword', [ProfileController::class, 'changePassword']);
    //
    Route::post('/updateProfile', [ProfileController::class, 'update']);

    //* Admin
    Route::middleware("role:admin")->group(function () {

        Route::get('/showUsers', [AdminController::class, 'showUsers']);

        Route::post('/blockUser', [AdminController::class, 'blockUser']);


        Route::post('/unBlockUser/{id}', [AdminController::class, 'unBlockUser']);
        Route::get('/getBlockedUsers', [AdminController::class, 'getBlockedUsers']);
        //
        Route::get('/checkIfUserBlocked/{id}', [AdminController::class, 'checkIfUserBlocked']);

        Route::post('getTransactionsByType', [AdminController::class, 'getTransactionsByType']);
        Route::post('getTransactionsByStatus', [AdminController::class, 'getTransactionsByStatus']);
        Route::post('handleDepositTransaction/{id}', [AdminController::class, 'handleDepositTransaction']);
        Route::post('handleWithdrawTransaction/{id}', [AdminController::class, 'handleWithdrawTransaction']);

        Route::post('handleAd/{id}', [AdminController::class, 'handleAdvertisment']);
        Route::post('/getAdsByStatus', [AdminController::class, 'getAdsByStatus']);

        Route::post('/getQuestionsByStatus', [AdminController::class, 'getQuestionsByStatus']);
        Route::post('/handleQuestion/{id}', [AdminController::class, 'handleQuestion']);

        //route for reports
        Route::get('/product-reports', [ReportController::class, 'productReports']);
        Route::post('/product-reports/{reportId}/dismiss', [ReportController::class, 'dismissProductReport']);
        Route::post('/product-reports/{reportId}/delete-product', [ReportController::class, 'deleteReportedProduct']);

        
        Route::get('/order-reports', [ReportController::class, 'OrderReports']);
        Route::post('/order-reports/{reportId}/accept', [ReportController::class, 'accept']);
        Route::post('/order-reports/{reportId}/reject', [ReportController::class, 'reject']);

    });




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


        
        Route::get('/ShowOrderBySeller', [OrderController::class, 'ShowOrderBySeller']);
        Route::post('/orders/{orderId}/ship', [OrderController::class, 'ship']);
        Route::delete('/orders/{orderId}/reject', [OrderController::class, 'reject']);
        Route::get('/orders/completed-count', [OrderController::class, 'completedCount']);

    });

    //* Customer
    Route::middleware("role:customer")->group(function () {
        Route::post('/deposit', [TransactionController::class, 'deposit']);

        Route::post('/addToFavorites/{id}', [CustomerController::class, 'addToFavorites']);
        Route::post('/removeFromFavorites/{id}', [CustomerController::class, 'removeFromFavorites']);
        Route::get('/getFavoriteProducts', [CustomerController::class, 'getFavoriteTasks']);

        Route::prefix('cart')->group(function () {

            Route::get('/', [CartController::class, 'index']);
            Route::post('/items', [CartController::class, 'addItem']);
            Route::put('/items/{cartItemId}', [CartController::class, 'updateItem']);
            Route::delete('/items/{cartItemId}', [CartController::class, 'removeItem']);
        });
        
        Route::get('/ShowOrderByCustomer', [OrderController::class, 'ShowOrderByCustomer']);
        Route::post('/storeorders', [OrderController::class, 'store']);
        Route::post('/order/{orderId}/confirm', [OrderController::class, 'confirm']);
        Route::post('/order/{orderId}/report', [ReportController::class, 'reportOrder']);

        Route::post('/rate-seller', [RatingController::class, 'store']);
    });


    //* Seller, Customer
    Route::middleware('role:customer,seller')->group(function () {

        Route::post('/changePin', [WalletController::class, 'changePin']);
        Route::post('/checkPin', [WalletController::class, 'checkPin']);
        Route::post('/createAd', [AdvertismentController::class, 'createAd']);
        Route::delete('/deleteAd/{id}', [AdvertismentController::class, 'deleteAd']);
        Route::get('/getMyAdsByStatus', [AdvertismentController::class, 'getMyAdsByStatus']);
        Route::get('/showAllAdvertisment', [AdvertismentController::class, 'showAllAdvertisment']);
        Route::get('/getMyTransactionByStatus', [TransactionController::class, 'getMyTransactionByStatus']);
        Route::get('/getMyBalance', [WalletController::class, 'getMyBalance']);

        Route::post('/askQuestion', [SupportRequestController::class, 'askQuestion']);
        Route::post('/getMyQuestionsByStatus', [SupportRequestController::class, 'getMyQuestionsByStatus']);

        Route::get('/showallproducts', [ProductController::class, 'showAllProducts']);
        Route::get('/products/{category_id}/categories', [ProductController::class, 'getProductByCategory']);
        Route::get('/products/searchByProductUrl', [ProductController::class, 'searchProductsByProductUrl']);
        Route::get('/products/filter', [ProductController::class, 'filterProducts']);
        
        Route::post('/products/{productId}/report', [ReportController::class, 'reportProduct']);
        Route::get('/sellers/{sellerId}/rating', [RatingController::class, 'sellerAverage']);



        Route::post('/withdraw', [TransactionController::class, 'withdraw']);
    });

    Route::get('/getAllMynotification', [NotificationController::class, 'Allnotification']);
    Route::put('/markasread/{id}', [NotificationController::class, 'MarkAsRead']);
    Route::delete('/deleteNotification/{id}', [NotificationController::class, 'deleteNotification']);

    //test
    Route::post('/sendNotification', [UserController::class, 'sendNotification']);


});
