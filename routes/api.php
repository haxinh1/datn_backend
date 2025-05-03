<?php

use App\Exports\OrderItemExport;
use App\Exports\ProductStocksExport;
use App\Http\Controllers\admin\AttributeController;
use App\Http\Controllers\admin\AttributeValueController;
use App\Http\Controllers\admin\BrandController;
use App\Http\Controllers\admin\CartItemController;
use App\Http\Controllers\admin\ChatSessionController;
use App\Http\Controllers\admin\MessageController;
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
use App\Http\Controllers\admin\BannedHistoryController;
use App\Http\Controllers\admin\OrderCancelController;
use App\Http\Controllers\admin\CommentController;
use App\Http\Controllers\admin\OrderReturnController;
use App\Http\Controllers\admin\UserController as AdminUserController;

use App\Http\Controllers\clients\UserController as ClientUserController;
use App\Http\Controllers\statistics\StatisticController;
use App\Http\Controllers\VNPayController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Broadcast;
use App\Http\Controllers\clients\ClientProductController;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\MomoController;


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

Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle']);

Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

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
Route::post('/import-stock', [StockController::class, 'import']);

Route::post('/export-product-stocks', function (Request $request) {
    $orderIds = $request->input('order_ids', []);
    if (empty($orderIds)) {
        return Excel::download(new ProductStocksExport(), 'product_stocks.xlsx');
    }
    return Excel::download(new ProductStocksExport($orderIds), 'product_stocks.xlsx');
});
Route::post('/export-orders', function (Request $request) {
    $orderIds = $request->input('order_ids', []);
    if (empty($orderIds)) {
        return Excel::download(new OrderItemExport(), 'order_item.xlsx');
    }
    return Excel::download(new OrderItemExport($orderIds), 'order_item.xlsx');
});
// Giỏ hàng (Cho phép khách vãng lai sử dụng)
Route::get('/cart', [CartItemController::class, 'index'])->name('cart.view');
Route::post('/cart/add/{id}', [CartItemController::class, 'store'])->name('cart.add');
Route::middleware('auth:sanctum')->group(function () {
    Route::put('/cart/update/{productId}/{variantId?}', [CartItemController::class, 'update'])->name('cart.update');
});
Route::delete('/cart/remove/{productId}/{variantId?}', [CartItemController::class, 'destroy'])->name('cart.remove');
Route::delete('/cart/destroy-all', [CartItemController::class, 'destroyAll'])->name('cart.destroyAll');

Route::get('user/{userId}/top-products', [OrderItemController::class, 'getTopProductsByUser']);
Route::get('/momo/callback', [MomoController::class, 'callback'])->name('momo.callback');

// Quản lý đơn hàng (Cho phép khách đặt hàng mà không cần đăng nhập)
Route::prefix('orders')->group(function () {
    Route::get('/', [OrderController::class, 'index'])->name('orders.view'); // Danh sách đơn hàng
    Route::post('/place', [OrderController::class, 'store'])->name('orders.place'); // Đặt hàng
    Route::get('/{id}', [OrderController::class, 'show'])->name('orders.show'); // Chi tiết đơn hàng
    Route::get('/code/{orderCode}', [OrderController::class, 'getOrderByCode']);
    Route::get('{orderId}/items', [OrderItemController::class, 'index']);
    Route::post('{orderId}/items', [OrderItemController::class, 'store']);
    Route::put('{orderId}/items/{itemId}', [OrderItemController::class, 'update']);
    Route::delete('{orderId}/items/{itemId}', [OrderItemController::class, 'destroy']);
    Route::get('/user/{userId}', [OrderController::class, 'getOrdersByUserId'])->name('orders.user');
    Route::post('/{orderId}/retry-payment', [OrderController::class, 'retryPayment']);
});

Route::prefix('order-returns')->group(function () {
    // Trả hàng
    Route::get('/', [OrderReturnController::class, 'index']); // Lấy danh sách các đơn hàng trả lại
    Route::get('/{orderId}', [OrderReturnController::class, 'show']); // Lấy chi tiết thông tin trả hàng
    Route::get('/user/{userId}', [OrderReturnController::class, 'showByUser']); // Trả hàng theo user
    Route::post('/{orderId}/return', [OrderReturnController::class, 'store']); // Đặt thông tin trả hàng
    Route::post('/approve-return/{orderId}', [OrderReturnController::class, 'approveReturn']);
    // Admin xử lý chấp nhận hoặc từ chối
    Route::post('/{orderId}/status/update', [OrderReturnController::class, 'updateStatusByOrder']);
    // Admin xác nhận hoàn tiền thành công
    Route::post('/{orderId}/refund/confirm', [OrderReturnController::class, 'confirmRefundByOrder']);
});



Route::get('/completed', [OrderController::class, 'completedOrders']);
Route::get('/accepted-returns', [OrderController::class, 'acceptedReturnOrders']);






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


Route::prefix('order-cancels')->group(function () {

    // Admin get all đơn hủy
    Route::get('/', [OrderCancelController::class, 'index']);

    // Client get đơn hủy theo user_id
    Route::get('/user/{userId}', [OrderCancelController::class, 'showByUser']);

    Route::get('/order/{orderId}', [OrderCancelController::class, 'showByOrderId']);

    // Client chủ động gửi yêu cầu hủy
    Route::post('/request-cancel', [OrderCancelController::class, 'clientRequestCancel']);

    // Admin chủ động hủy đơn
    Route::post('/admin-cancel/{orderId}', [OrderCancelController::class, 'adminCancelOrder']);

    // Client bổ sung bank info sau khi admin yêu cầu
    Route::post('/submit-bank-info/{cancelId}', [OrderCancelController::class, 'clientSubmitBankInfo']);

    // Admin hoàn tiền (upload minh chứng)
    Route::post('/refund/{cancelId}', [OrderCancelController::class, 'adminRefundOrder']);
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
Route::get('/orders/modified-by/{modified_by}', [OrderOrderStatusController::class, 'getOrdersByModifiedBy']);
Route::put('/orders/{id}/update-status', [OrderOrderStatusController::class, 'updateStatus'])
    ->name('orders.updateStatus');
Route::middleware('auth:sanctum')->group(function () {
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
    Route::get('/orders/search', [SearchController::class, 'searchOrders']);
    Route::get('/orders-return/search', [SearchController::class, 'searchOrderReturn']);
    Route::get('/orders-cancel/search', [SearchController::class, 'searchOrderCancel']);
});
//route admin user
Route::prefix('admin')->group(function () {
    Route::get('/users/search', [SearchController::class, 'searchUsers']);
    Route::get('/users', [AdminUserController::class, 'index']);
    Route::get('/users/customer', [AdminUserController::class, 'index1']);
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
Route::post('/resend-code', [ClientUserController::class, 'resendVerificationCode']);
Route::post('/verify-email', [ClientUserController::class, 'verifyEmail']);
Route::post('/login', [ClientUserController::class, 'login']);
Route::post('/logout', [ClientUserController::class, 'logout'])->middleware('auth:sanctum');
Route::post('/broadcasting/auth', function (Request $request) {
    return Broadcast::auth($request);
})->middleware('auth:sanctum');
Route::put('/change-password/{id}', [AdminUserController::class, 'changePassword'])->middleware('auth:sanctum');
Route::post('/forgot-password', [ClientUserController::class, 'forgotPassword']);
Route::post('/reset-password', [ClientUserController::class, 'resetPassword']);
// ->middleware(['auth:api'])
Route::prefix('banned-history')->group(function () {
    Route::get('/', [BannedHistoryController::class, 'index']);
    Route::get('/{user_id}', [BannedHistoryController::class, 'show']);
    Route::post('/', [BannedHistoryController::class, 'store']);
    Route::post('/{id}/unban', [BannedHistoryController::class, 'unban']);
});

// user address
Route::middleware('auth:sanctum')->group(function () {
    Route::get('user-addresses', [UserAddressController::class, 'index']);
    Route::get('user-addresses/{user_id}', [UserAddressController::class, 'show']);
    Route::get('useraddress-addresses/{id}', [UserAddressController::class, 'showidAdress']);
    Route::get('user-addresses/{user_id}', [UserAddressController::class, 'show']);
    Route::post('user-addresses', [UserAddressController::class, 'store']);
    Route::put('user-addresses/{id}', [UserAddressController::class, 'update']);
    Route::delete('user-addresses/{id}', [UserAddressController::class, 'destroy']);
});

// lịch sử điểm
Route::get('user/points/{id}', [AdminUserController::class, 'pointsHistory']);

// Category apiii


// Api url = "baseUrl/api/categories"
Route::get('categories', [CategoryController::class, 'index']); // Lấy danh sách danh mục
Route::get('product-by-category/{id}', [CategoryController::class, 'getProductByCategory']); // Lấy danh sách danh mục
Route::get('categories/{category}', [CategoryController::class, 'show']); // Lấy thông tin danh mục cụ thể
Route::post('cateadminCaes/create', [CategoryController::class, 'create']);
Route::put('categories/update/{category}', [CategoryController::class, 'update']);
Route::put('categories/update-status/{category}', [CategoryController::class, 'updateStatus']);
Route::delete('categories/delete/{category}', [CategoryController::class, 'destroy']);


// Api Coupon
Route::get('coupons', [CouponController::class, 'index']);
Route::get('coupons/search/filter', [CouponController::class, 'search']); // coupons/search/filter?code=SUMMER&discount_type=fix_amount&is_active=1&page=1
Route::get('coupons/{id}', [CouponController::class, 'show']);
Route::post('coupons/create', [CouponController::class, 'store']);
Route::put('coupons/{id}', [CouponController::class, 'update']);

Route::get('coupons/available/coupons', [CouponController::class, 'availableCoupons']);



Route::prefix('comments')->group(function () {
    Route::get('/', [CommentController::class, 'index']); // Lấy danh sách bình luận
    Route::get('/{id}', [CommentController::class, 'detail']); // Lấy chi tiết bình luận
    Route::put('/{id}', [CommentController::class, 'updateComment']); // Cập nhật trạng thái bình luận
    Route::post('/{id}/update', [CommentController::class, 'update']); // Cập nhật trạng thái bình luận
    Route::post('/bulk-action', [CommentController::class, 'bulkAction']); //  // Duỵyệt nhiều comment
    Route::post('/', [CommentController::class, 'store']); //  Tạo commet
    Route::get('/user/{user_id}', [CommentController::class, 'showIduser']); // Lấy danh sách bình luận theo sản phẩm
    Route::get('/product/{productId}', [CommentController::class, 'getCommentsByProduct']);
    //    Kiểm tra xem người dùng hiện tại còn bao nhiêu lượt bình luận cho một sản phẩm cụ thể, dựa trên số lần đã mua và đã bình luận.
    Route::get('/product/can-comment/{productId}', [CommentController::class, 'remainingCommentCountByProduct']);
});


Route::prefix('chat')->group(function () {
    // Tạo 1 phiên chat
    Route::post('/create-session', [ChatSessionController::class, 'createSession']);
    // Danh sách phiên chat
    Route::get('/sessions', [ChatSessionController::class, 'getSessions']);
    // Đóng phiên chat
    Route::post('/close-session/{id}', [ChatSessionController::class, 'closeSession']);

    // Tin nhắn
    // Gửi tin nhắn  cho cả admin và client
    Route::post('/send-message', [MessageController::class, 'sendMessage']);

    // Lấy danh sách tin nhắn trong 1 phiên chat
    Route::get('/messages/{chatSessionId}', [MessageController::class, 'getMessages']);

    Route::post('/mark-as-read/{id}', [MessageController::class, 'markAsRead']);
});

//Client
Route::get('/product-detail/{id}', [ClientProductController::class, 'productDetail']);
Route::get('/product-recommend/{id}', [ClientProductController::class, 'getRecommendedProducts']);
Route::post('/search-image', [ClientProductController::class, 'searchImage']);


//Thống kê

Route::prefix('statistics')->group(function () {
    //Top 10 người dùng mua sản phẩm nhiều nhất
    Route::get('/top-user-bought', [StatisticController::class, 'topUserBought']);
    //Top 10 sản phẩm có số lương bán ra nhiều nhất
    Route::get('/top-product-bought', [StatisticController::class, 'topProductBought']);
    //Doanh thu theo ngày theo tháng theo năm
    Route::get('/revenue', [StatisticController::class, 'revenue']);
    //Tổng đơn theo trạng thái
    Route::get('/order-statistics', [StatisticController::class, 'orderStatistics']);
    //TOP 10 sản phẩm được mua nhiều nhất và 10 sản phẩm có lượt xem nhiều nhất
    Route::get('/top-buy-view', [StatisticController::class, 'topBuyView']);
    //Top doanh thu cao nhất theo ngày
    Route::get('/top-revenue-days', [StatisticController::class, 'topRevenueDays']);
    //Biểu đồ hoàn hủy
    Route::get('/revenue-statistics', [StatisticController::class, 'revenueStatistics']);
    //Thống kê nhập hàng theo ngày
    Route::get('/revenue-stocks', [StatisticController::class, 'revenueStock']);
});
