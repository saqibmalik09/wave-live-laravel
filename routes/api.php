<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
/*

|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/



Route::middleware(['check.maintenance'])->group(function () {
// Public Routes
Route::post('/simple-login', [AuthController::class, 'simplelogin']);
Route::post('/simple-register', [AuthController::class, 'simpleregister']);
Route::post('reset-password',[AuthController::class,'resetPassword']);
Route::post('verify-otp',[AuthController::class,'verifyOtpAndResetPassword']);
// Protected Routes (Requires valid JWT)
Route::middleware(['auth:api'])->group(function () {
    Route::get('user', [AuthController::class, 'userProfile']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::get('refresh-token', [AuthController::class, 'refreshToken']);
    Route::post('delete-user',[AuthController::class,'deleteAccount']);
    Route::post('change-password',[AuthController::class,'changePassword']);
    Route::post('update-my-profile',[AuthController::class,'updateMyProfile']);
});

});
