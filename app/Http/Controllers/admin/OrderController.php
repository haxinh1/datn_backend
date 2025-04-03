<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\VNPayController;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderMail;
use App\Models\Coupon;

class OrderController extends Controller
{
    /**
     * Lấy danh sách đơn hàng
     */
    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['orders' => $orders], 200);
    }
    // Lọc theo userId
    public function getOrdersByUserId($userId)
    {
        $orders = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json(['orders' => $orders], 200);
    }
    public function completedOrders(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->whereIn('status_id', [7, 11])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No completed orders found.'], 404);
        }

        return response()->json(['orders' => $orders], 200);
    }
    public function acceptedReturnOrders(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->where('status_id', 10)  // Trạng thái "Chấp nhận trả hàng"
            ->orderBy('created_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'No orders accepted for return found.'], 404);
        }

        return response()->json(['orders' => $orders], 200);
    }


    public function show($id)
    {
        // Lấy đơn hàng theo ID
        $order = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->where('id', $id)
            ->orderBy('created_at', 'desc')
            ->first();
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json(['order' => $order], 200);
    }

    /**
     * Đặt hàng (Thanh toán COD hoặc chuyển khoản)
     */
    public function store(Request $request)
    {
        Log::info('DEBUG - Toàn bộ giỏ hàng khi đặt hàng:', ['cart_items' => $request->cart_items]);

        DB::beginTransaction();

        try {
            // Lấy userId từ frontend hoặc local
            $userId = $request->input('user_id') ?? null;
            // Kiểm tra nếu đã đăng nhập
            $user = $userId ? User::find($userId) : null;

            // Nếu người dùng đã đăng nhập, lấy giỏ hàng từ database
            if ($userId) {
                $cartItems = CartItem::where('user_id', $userId)->with('product', 'productVariant')->get();
            } else {
                // Giỏ hàng sẽ được lấy từ session khi khách chưa đăng nhập
                $cartItems = collect($request->input('cart_items'));  // Nhận giỏ hàng từ frontend
            }

            // Kiểm tra nếu giỏ hàng trống
            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Giỏ hàng trống'], 400);
            }
            // Kiểm tra tồn kho trước khi đặt hàng
            foreach ($cartItems as $item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    return response()->json(['message' => "Sản phẩm ID {$item['product_id']} không tồn tại"], 400);
                }

                $availableStock = $item['product_variant_id']
                    ? ProductVariant::where('id', $item['product_variant_id'])->value('stock')
                    : $product->stock;

                if ($availableStock < $item['quantity']) {
                    return response()->json([
                        'message' => "Sản phẩm '{$product->name}' không đủ số lượng trong kho. Chỉ còn $availableStock sản phẩm."
                    ], 400);
                }
            }

            // Tính tổng tiền đơn hàng
            $totalAmount = $cartItems->sum(function ($item) {
                $product = Product::find($item['product_id']);

                if (!empty($item['product_variant_id'])) {
                    $productVariant = ProductVariant::where('id', $item['product_variant_id'])->first();
                    $price = $productVariant ? ($productVariant->sale_price ?? $productVariant->sell_price) : 0;
                } else {
                    $price = $product->sale_price ?? $product->sell_price;
                }

                return $item['quantity'] * $price;
            });

            // Kiểm tra và áp dụng mã giảm giá
            $couponCode = $request->input('coupon_code');
            $discountAmount = 0;
            if ($couponCode) {
                // Kiểm tra xem mã giảm giá có hợp lệ không
                $coupon = Coupon::where('code', $couponCode)->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if (!$coupon) {
                    return response()->json(['message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'], 400);
                }

                // Tính toán giá trị giảm giá dựa trên loại mã giảm giá (percent hoặc fix_amount)
                if ($coupon->discount_type == 'percent') {
                    $discountAmount = ($coupon->discount_value / 100) * $totalAmount;
                } else {
                    $discountAmount = $coupon->discount_value;
                }

                // Đảm bảo không giảm quá số tiền tổng
                if ($discountAmount > $totalAmount) {
                    $discountAmount = $totalAmount;
                }

                // Trừ giá trị giảm giá vào tổng tiền
                $totalAmount -= $discountAmount;

                Log::info("DEBUG - Áp dụng mã giảm giá: {$couponCode}, giảm giá: $discountAmount, tổng tiền sau giảm: $totalAmount");
            }
            // Nhận phí ship từ frontend
            $shippingFee = $request->input('shipping_fee', 0);
            $totalAmount += $shippingFee;


            $usedPoints = $request->input('used_points', 0);
            $discountPoints = 0;


            if ($userId) {
                if ($usedPoints > $user->loyalty_points) {
                    return response()->json(['message' => 'Số điểm không hợp lệ'], 400);
                }
                $discountPoints = $usedPoints;
                $totalAmount -= $discountPoints; // Trừ điểm thưởng vào tổng tiền
            }

            // Đảm bảo tổng tiền không âm
            if ($totalAmount < 0) {
                $totalAmount = 0;
            }

            Log::info('DEBUG - Tổng tiền sau khi áp dụng giảm giá và điểm thưởng:', ['total_amount' => $totalAmount]);


            // Nếu user có điểm thưởng và đã sử dụng, trừ điểm trong database
            if ($userId && $usedPoints > 0) {
                $user->decrement('loyalty_points', $usedPoints);

                $updatedPoints = $user->fresh()->loyalty_points;

                Log::info('DEBUG - Trừ điểm thành công:', [
                    'user_id' => $userId,
                    'used_points' => $usedPoints,
                    'remaining_points' => $updatedPoints
                    //  $user->loyalty_points - $usedPoints
                ]);
            }



            // Kiểm tra thông tin khách hàng nếu chưa đăng nhập
            $request->validate([
                'fullname' => $user ? 'nullable' : 'required|string|max:255',
                'email' => $user ? 'nullable' : 'required|email|max:255',
                'phone_number' => $user ? 'nullable' : 'required|string|max:20',
                'address' => $user ? 'nullable' : 'required|string|max:255',
            ]);

            // Lấy thông tin khách hàng
            $fullname = $user->fullname ?? $request->fullname ?? '';
            $email = $user->email ?? $request->email ?? '';
            $phone_number = $user->phone_number ?? $request->phone_number ?? '';


            // Nếu người dùng nhập địa chỉ mới, ưu tiên địa chỉ mới
            if ($request->filled('address')) {
                $address = $request->address;
            } else {
                // Nếu không, lấy địa chỉ từ database
                $address = $user ? $user->addresses()->where('id_default', true)->value('address') : null;
                if ($user && !$address) {
                    $address = $user->addresses()->orderByDesc('created_at')->value('address');
                }
            }

            // Nếu không có địa chỉ nào hợp lệ, báo lỗi
            if (!$address) {
                return response()->json(['message' => 'Bạn chưa có địa chỉ, vui lòng cập nhật'], 400);
            }


            // Nếu user chưa có địa chỉ, lưu lại địa chỉ mới
            if ($user && !$user->addresses()->exists() && $request->address) {
                $user->addresses()->create([
                    'address' => $request->address,
                    'id_default' => true
                ]);
            }

            // Kiểm tra phương thức thanh toán
            $paymentMethod = strtolower($request->input('payment_method', ''));
            if (!$paymentMethod) {
                return response()->json(['message' => 'Thiếu phương thức thanh toán'], 400);
            }

            // Kiểm tra nếu khách vãng lai, chỉ cho phép VNPay
            if (!$userId && $paymentMethod != 'vnpay') {
                return response()->json(['message' => 'Khách vãng lai chỉ có thể thanh toán qua VNPay'], 400);
            }

            // Kiểm tra nếu phương thức thanh toán không hợp lệ (cho phép cả VNPay và COD cho người dùng đã đăng nhập)
            if (!in_array($paymentMethod, ['vnpay', 'cod'])) {
                return response()->json(['message' => 'Phương thức thanh toán không hợp lệ'], 400);
            }
            // Lấy ID phương thức thanh toán từ bảng payments
            $payment = DB::table('payments')
                ->whereRaw('LOWER(name) = ?', [strtolower($paymentMethod)])
                ->first();
            $paymentId = $payment ? $payment->id : null;

            if (!$paymentId) {
                return response()->json(['message' => 'Phương thức thanh toán không hợp lệ'], 400);
            }
            Log::info('DEBUG - Tổng tiền đơn hàng:', ['total_amount' => $totalAmount]);
            // Tạo đơn hàng
            $order = Order::create([
                'code' => 'ORD' . strtoupper(Str::random(8)),
                'user_id' => $userId,
                'fullname' => $fullname,
                'email' => $email,
                'phone_number' => $phone_number,
                'address' => $address,
                'total_amount' => $totalAmount,
                'shipping_fee' => $shippingFee,
                'status_id' => ($paymentMethod == 'vnpay') ? 1 : 3,
                'payment_id' => $paymentId,
                'used_points' => $usedPoints,
                'discount_points' => $discountPoints,
                'coupon_code' => $couponCode,
                'discount_amount' => $discountAmount,
                'coupon_id' => $coupon ? $coupon->id : null, 
                'coupon_description' => $coupon ? $coupon->description : null, 
                'coupon_discount_type' => $coupon ? $coupon->discount_type : null, 
                'coupon_discount_value' => $coupon ? $coupon->discount_value : null, 
            ]);

            // Lưu chi tiết đơn hàng và cập nhật tồn kho
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'sell_price' => $item['product_variant_id']
                        ? ProductVariant::where('id', $item['product_variant_id'])->value('sale_price') ?? ProductVariant::where('id', $item['product_variant_id'])->value('sell_price')
                        : Product::where('id', $item['product_id'])->value('sale_price') ?? Product::where('id', $item['product_id'])->value('sell_price'),
                ]);
                // Trừ stock nếu chọn COD
                if ($paymentMethod == 'cod') {
                    if ($item['product_variant_id']) {
                        ProductVariant::where('id', $item['product_variant_id'])->decrement('stock', $item['quantity']);
                    } else {
                        Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
                    }
                }
            }

            // Xóa giỏ hàng sau khi đặt hàng thành công**
            if ($userId) {
                CartItem::where('user_id', $userId)->delete();
            }

            // Nếu chọn VNPay, gọi VNPayController để tạo URL thanh toán
            if ($paymentMethod == 'vnpay') {
                DB::commit();

                $vnpayController = app()->make(VNPayController::class);
                return $vnpayController->createPayment(new Request([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod
                ]));
            }

            // Nếu chọn COD, đơn hàng được xác nhận ngay lập tức
            $order->update(['status_id' => 3]);
            DB::commit();

            return response()->json(['message' => 'Đặt hàng thành công!', 'order' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }
    public function retryPayment($orderId)
    {
        // Lấy đơn hàng theo ID và kiểm tra trạng thái
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }

        if ($order->status_id != 1) {
            return response()->json(['message' => 'Đơn hàng không thể thanh toán lại, trạng thái không hợp lệ'], 400);
        }

        // Lấy phương thức thanh toán (VNPay hoặc COD)
        $paymentMethod = 'vnpay';

        // Tạo yêu cầu thanh toán VNPay
        $vnpayController = app()->make(VNPayController::class);

        return $vnpayController->createPayment(new Request([
            'order_id' => $order->id,
            'payment_method' => $paymentMethod
        ]));
    }
}
