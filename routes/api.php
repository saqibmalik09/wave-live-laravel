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
// Protected Routes (Requires valid JWT)
Route::middleware(['auth:api'])->group(function () {
    Route::get('user', [AuthController::class, 'userProfile']);
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('delete-user',[AuthController::class,'deleteAccount']);
});

});
