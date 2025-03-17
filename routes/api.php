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
use App\Http\Controllers\admin\OrderItemController;
use App\Http\Controllers\admin\OrderOrderStatusController;
use App\Http\Controllers\admin\ProductVariantController;
use App\Http\Controllers\admin\SearchController;
use App\Http\Controllers\admin\StockController;
use App\Http\Controllers\admin\TagController;
use App\Http\Controllers\admin\UserAddressController;
use App\Http\Controllers\admin\UserController as AdminUserController;

use App\Http\Controllers\clients\UserController as ClientUserController;
use App\Http\Controllers\VNPayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\admin\CommentController;

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
// Route::resource('/products', ProductController::class);
Route::get('/products/filter', [ProductController::class, 'filterProducts']);
Route::get('/products', [ProductController::class, 'index']); 
Route::post('/products', [ProductController::class, 'store']); 
Route::get('/products/{id}', [ProductController::class, 'show']); 
Route::put('/products/{id}', [ProductController::class, 'update']); 
Route::delete('/products/{id}', [ProductController::class, 'destroy']);
Route::put('/products/edit/active/{id}', [ProductController::class, 'active']);

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
Route::put('/cart/update/{productId}/{variantId?}', [CartItemController::class, 'update'])->name('cart.update');
Route::delete('/cart/remove/{productId}/{variantId?}', [CartItemController::class, 'destroy'])->name('cart.remove');


// Quản lý đơn hàng (Cho phép khách đặt hàng mà không cần đăng nhập)
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.view'); // Danh sách đơn hàng
    Route::post('/place', [OrderController::class, 'store'])->name('orders.place'); // Đặt hàng
    Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show'); // Chi tiết đơn hàng
    Route::post('/{id}/update-status', [OrderController::class, 'updateStatus'])->name('orders.updateStatus'); // Cập nhật trạng thái
    Route::get('{orderId}/items', [OrderItemController::class, 'index']);
    Route::post('{orderId}/items', [OrderItemController::class, 'store']);
    Route::put('{orderId}/items/{itemId}', [OrderItemController::class, 'update']);
    Route::delete('{orderId}/items/{itemId}', [OrderItemController::class, 'destroy']);
    Route::get('/user/{userId}', [OrderController::class, 'getOrdersByUserId'])->name('orders.user');
});

Route::prefix('payments')->group(function () {
    Route::post('/', [PaymentController::class, 'store']); // Tạo mới
    Route::get('/', [PaymentController::class, 'index']); // Lấy danh sách
    Route::get('/{id}', [PaymentController::class, 'show']); // Lấy chi tiết
    Route::put('/{id}', [PaymentController::class, 'update']); // Cập nhật
    Route::delete('/{id}', [PaymentController::class, 'destroy']); // Xóa

    // VNPay Payment
    Route::post('/vnpay', [VNPayController::class, 'createPayment'])->name('payment.process');
    Route::get('/vnpay/return', [VNPayController::class, 'paymentReturn'])->name('payment.vnpayReturn');
});


// Quản lí trạng thái
Route::prefix('order-statuses')->group(function () {
    Route::get('/', [OrderStatusController::class, 'index']);
    Route::post('/', [OrderStatusController::class, 'store']);
    Route::get('/{id}', [OrderStatusController::class, 'show']);
    Route::put('/{id}', [OrderStatusController::class, 'update']);
    Route::delete('/{id}', [OrderStatusController::class, 'destroy']); // Xóa nếu không có đơn hàng nào đang sử dụng
});



// Quản lý lịch sử trạng thái đơn hàng
Route::get('/orders/{id}/statuses', [OrderOrderStatusController::class, 'index'])->name('orders.statuses');
Route::post('/orders/multiple-statuses', [OrderOrderStatusController::class, 'indexMultiple']);
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/orders/{id}/update-status', [OrderOrderStatusController::class, 'updateStatus'])
        ->name('orders.updateStatus');
    Route::put('/orders/batch-update-status', [OrderOrderStatusController::class, 'batchUpdateByStatus'])
        ->name('orders.batchUpdateStatus');
});







Route::apiResource('tags', TagController::class);
Route::apiResource('coupons', CouponController::class);


// Route::apiResource('users', AdminUserController::class);
// sreach
Route::prefix('admin')->group(function () {
    Route::get('/products/search', [SearchController::class, 'searchProducts']);
    Route::get('/users/search', [SearchController::class, 'searchUsers']);
});
//route admin user
Route::prefix('admin')->group(function () {
    Route::get('/users/search', [SearchController::class, 'searchUsers']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/{id}', [AdminUserController::class, 'show']);
    Route::post('/users', [AdminUserController::class, 'store']);
    Route::put('/users/{id}', [AdminUserController::class, 'update']);
    Route::delete('/users/{id}', [AdminUserController::class, 'destroy']);

    Route::post('/login', [AdminUserController::class, 'login']);
    Route::post('/logout', [AdminUserController::class, 'logout'])->middleware('auth:sanctum');
    Route::put('/change-password/{id}', [AdminUserController::class, 'changePassword'])->middleware('auth:sanctum');
});
// route login user

Route::post('/register', [ClientUserController::class, 'register']);
Route::post('/verify-email', [ClientUserController::class, 'verifyEmail']);
Route::post('/login', [ClientUserController::class, 'login']);
Route::post('/logout', [ClientUserController::class, 'logout'])->middleware('auth:sanctum');
Route::put('/change-password/{id}', [AdminUserController::class, 'changePassword'])->middleware('auth:sanctum');
Route::post('/forgot-password', [ClientUserController::class, 'forgotPassword']);
Route::post('/reset-password', [ClientUserController::class, 'resetPassword']);

// user address
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user-addresses', [UserAddressController::class, 'index']);
    Route::get('user-addresses/{user_id}', [UserAddressController::class, 'show']); 
    Route::post('user-addresses', [UserAddressController::class, 'store']);
    Route::put('user-addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('user-addresses/{id}', [UserAddressController::class, 'destroy']);
});



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
Route::get('coupons/{id}', [CouponController::class, 'show']);
Route::post('coupons/create', [CouponController::class, 'store']);
Route::put('coupons/{id}', [CouponController::class, 'update']);



Route::prefix('comments')->group(function () {
    Route::get('/', [CommentController::class, 'index']); // Lấy danh sách bình luận
    Route::get('/{id}', [CommentController::class, 'detail']); // Lấy chi tiết bình luận
    Route::put('/{id}', [CommentController::class, 'updateComment']); // Cập nhật trạng thái bình luận
    Route::post('/{id}/update', [CommentController::class, 'update']); // Cập nhật trạng thái bình luận
    Route::post('/bulk-action', [CommentController::class, 'bulkAction']); //  // Duỵyệt nhiều comment
    Route::post('/', [CommentController::class, 'store']); //  // Duỵyệt nhiều comment
});
