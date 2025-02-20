<?php

use App\Http\Controllers\admin\AttributeController;
use App\Http\Controllers\admin\AttributeValueController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\CartItemController;
use App\Http\Controllers\admin\ProductController;
use App\Http\Controllers\admin\CategoryController;
use App\Http\Controllers\admin\PaymentController;
use App\Http\Controllers\admin\OrderStatusController;
use App\Http\Controllers\admin\CouponController;
use App\Http\Controllers\admin\OrderController;
use App\Http\Controllers\admin\OrderOrderStatusController;
use App\Http\Controllers\admin\ProductVariantController;
use App\Http\Controllers\admin\StockController;
use App\Http\Controllers\admin\TagController;

use App\Http\Controllers\admin\UserController as AdminUserController;

use App\Http\Controllers\clients\UserController as ClientUserController;
use App\Http\Controllers\VNPayController;
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

//Route thuộc tính
Route::apiResource('/attributes', AttributeController::class);
//Route giá trị thuộc tính
Route::resource('/attributeValue', AttributeValueController::class);
//Route brand
Route::resource('/brands', BrandController::class);
//Route biến thể sản phẩm
Route::resource('/productVariant', ProductVariantController::class);
//Route product
Route::resource('/products', ProductController::class);
//Active sản phẩm
Route::put('/products/edit/active/{id}', [ProductController::class, 'active']);
//Active biến thể
Route::put('/productVariant/edit/active/{id}', [ProductVariantController::class, 'active']);
//Nhập kho
Route::post('postStock', [StockController::class, 'store'])->name('postStock');
Route::resource('/stocks', StockController::class);


// Giỏ hàng (Cho phép khách vãng lai sử dụng)
Route::get('/cart', [CartItemController::class, 'index'])->name('cart.view');
Route::post('/cart/add/{id}', [CartItemController::class, 'store'])->name('cart.add');
Route::post('/cart/update/{id}', [CartItemController::class, 'update'])->name('cart.update');
Route::get('/cart/remove/{id}', [CartItemController::class, 'destroy'])->name('cart.remove');

// Quản lý đơn hàng (Cho phép khách đặt hàng mà không cần đăng nhập)
Route::get('/orders', [OrderController::class, 'index'])->name('orders.view');
Route::post('/orders/place', [OrderController::class, 'store'])->name('orders.place');
Route::post('/orders/{id}/update-status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus');

// Quản lý lịch sử trạng thái đơn hàng (Chỉ user đăng nhập mới xem được)
Route::middleware('auth')->group(function () {
    // Quản lý trạng thái đơn hàng
    Route::get('/orders/{id}/statuses', [OrderOrderStatusController::class, 'index'])->name('orders.statuses');
    Route::post('/orders/{id}/update-status', [OrderOrderStatusController::class, 'updateStatus'])->name('orders.updateStatus');
});

// Thanh toán VNPay (Khách vãng lai cũng có thể thanh toán)
Route::post('/payment/vnpay', [VNPayController::class, 'createPayment'])->name('vnpay.payment');
Route::get('/payment/vnpay/return', [VNPayController::class, 'paymentReturn'])->name('vnpay.return');

Route::apiResource('tags', TagController::class);
Route::apiResource('coupons', CouponController::class);


Route::apiResource('users', AdminUserController::class);

//route login admin
Route::prefix('admin')->group(function () {
    Route::post('/login', [AdminUserController::class, 'login']);
    Route::post('/logout', [AdminUserController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/change-password', [AdminUserController::class, 'changePassword'])->middleware('auth:sanctum');
});
// route login client
Route::prefix('client')->group(function () {
    Route::post('/register', [ClientUserController::class, 'register']);
    Route::post('/login', [ClientUserController::class, 'login']);
    Route::post('/logout', [ClientUserController::class, 'logout'])->middleware('auth:sanctum');
    Route::post('/change-password', [ClientUserController::class, 'changePassword'])->middleware('auth:sanctum');
    Route::post('/forgot-password', [ClientUserController::class, 'forgotPassword']);
    Route::post('/reset-password', [ClientUserController::class, 'resetPassword']);
});



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


// Api Coupon
Route::get('coupons', [CouponController::class, 'index']);
Route::get('coupons/search/filter', [CouponController::class, 'search']); // coupons/search/filter?code=SUMMER&discount_type=fix_amount&is_active=1&page=1
Route::get('coupons/${id}', [CouponController::class, 'show']);
Route::post('coupons/create', [CouponController::class, 'store']);
Route::put('coupons/${id}', [CouponController::class, 'update']);
