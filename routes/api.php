<?php

use App\Http\Controllers\admin\AttributeController;
use App\Http\Controllers\admin\AttributeValueController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\CategoryController;
use App\Http\Controllers\admin\PaymentController;
use App\Http\Controllers\admin\OrderStatusController;
use App\Http\Controllers\admin\CouponController;
use App\Http\Controllers\admin\ProductStockController;
use App\Http\Controllers\admin\ProductVariantController;
use App\Http\Controllers\admin\TagController;

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


Route::apiResource('/attributes', AttributeController::class);
Route::resource('/attributeValue', AttributeValueController::class);
Route::resource('/brands', BrandController::class);
Route::resource('/productVariant', BrandController::class);
Route::resource('/products', ProductController::class);
Route::put('/products/edit/active/{id}',[ProductController::class,'active']);
Route::put('/productVariant/edit/active/{id}',[ProductVariantController::class,'active']);
Route::get('allVariant',[ProductVariantController::class,'index'])->name('allVariant');
Route::post('postStock',[ProductStockController::class,'store'])->name('postStock');


Route::apiResource('tags', TagController::class);
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
Route::put('categories/update-status/{category}', [CategoryController::class, 'updateStatus']);
Route::delete('categories/delete/{category}', [CategoryController::class, 'destroy']);
