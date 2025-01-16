<?php

use App\Http\Controllers\admin\AttributeController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\OrderStatusController;
use App\Http\Controllers\CouponController;
use App\Http\Controllers\TagController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::resource('attributes', AttributeController::class);
Route::resource('brands', BrandController::class);


Route::apiResource('tags', TagController::class);
Route::post('/coupons/{id}/restore', [CouponController::class, 'restore']);
Route::apiResource('coupons', CouponController::class);

//payment
Route::prefix('payments')->group(function () {
    
    Route::post('/', [PaymentController::class, 'store']);      // Tạo mới
    Route::get('/', [PaymentController::class, 'index']);       // Lấy danh sách
    Route::get('/{id}', [PaymentController::class, 'show']);    // Lấy chi tiết
    Route::put('/{id}', [PaymentController::class, 'update']);  // Cập nhật
    Route::delete('/{id}', [PaymentController::class, 'destroy']); // Xóa
});


Route::get('/order-statuses', [OrderStatusController::class, 'index']);
Route::post('/order-statuses', [OrderStatusController::class, 'store']);
Route::get('/order-statuses/{id}', [OrderStatusController::class, 'show']);
Route::put('/order-statuses/{id}', [OrderStatusController::class, 'update']);
Route::delete('/order-statuses/{id}', [OrderStatusController::class, 'destroy']);


// Category api


// Api url = "baseUrl/api/categories"
Route::get('categories', [CategoryController::class, 'index']); // Lấy danh sách danh mục
Route::get('categories/{category}', [CategoryController::class, 'show']); // Lấy thông tin danh mục cụ thể
Route::post('categories/create', [CategoryController::class, 'create']);
Route::put('categories/update/{category}', [CategoryController::class, 'update']);
Route::delete('categories/delete/{category}', [CategoryController::class, 'destroy']);
