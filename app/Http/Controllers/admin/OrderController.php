<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Http\Controllers\MomoController;
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
use App\Models\UserPointTransaction;

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
            $coupon = null;
            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if (!$coupon) {
                    return response()->json(['message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'], 400);
                }

                if ($coupon->discount_type == 'percent') {
                    $discountAmount = ($coupon->discount_value / 100) * $totalAmount;
                } else {
                    $discountAmount = $coupon->discount_value;
                }

                if ($discountAmount > $totalAmount) {
                    $discountAmount = $totalAmount;
                }

                $totalAmount -= $discountAmount;

                Log::info("DEBUG - Áp dụng mã giảm giá: {$couponCode}, giảm giá: $discountAmount, tổng tiền sau giảm: $totalAmount");
            }
            $shippingFee = $request->input('shipping_fee', 0);
            $totalAmount += $shippingFee;

            $usedPoints = $request->input('used_points', 0);
            $discountPoints = 0;


            if ($userId) {
                if ($usedPoints > $user->loyalty_points) {
                    return response()->json(['message' => 'Số điểm không hợp lệ'], 400);
                }
                $discountPoints = $usedPoints;
                $totalAmount -= $discountPoints;
            }

            if ($totalAmount < 0) {
                $totalAmount = 0;
            }

            Log::info('DEBUG - Tổng tiền sau khi áp dụng giảm giá và điểm thưởng:', ['total_amount' => $totalAmount]);

            // Tổng tiền sản phẩm 
            $totalProductAmount = $totalAmount + $discountAmount + $usedPoints - $shippingFee;

            if ($userId && $usedPoints > 0) {
                $user->decrement('loyalty_points', $usedPoints);
                $updatedPoints = $user->fresh()->loyalty_points;

                Log::info('DEBUG - Trừ điểm thành công:', [
                    'user_id' => $userId,
                    'used_points' => $usedPoints,
                    'remaining_points' => $updatedPoints
                ]);
            }

            $request->validate([
                'fullname' => $user ? 'nullable' : 'required|string|max:255',
                'email' => $user ? 'nullable' : 'required|email|max:255',
                'phone_number' => $user ? 'nullable' : 'required|string|max:20',
                'address' => $user ? 'nullable' : 'required|string|max:255',
            ]);

            $fullname = $user->fullname ?? $request->fullname ?? '';
            $email = $user->email ?? $request->email ?? '';
            $phone_number = $user->phone_number ?? $request->phone_number ?? '';

            if ($request->filled('address')) {
                $address = $request->address;
            } else {
                $address = $user ? $user->addresses()->where('id_default', true)->value('address') : null;
                if ($user && !$address) {
                    $address = $user->addresses()->orderByDesc('created_at')->value('address');
                }
            }

            if (!$address) {
                return response()->json(['message' => 'Bạn chưa có địa chỉ, vui lòng cập nhật'], 400);
            }

            if ($user && !$user->addresses()->exists() && $request->address) {
                $user->addresses()->create([
                    'address' => $request->address,
                    'id_default' => true
                ]);
            }

            $paymentMethod = strtolower($request->input('payment_method', ''));
            if (!$paymentMethod) {
                return response()->json(['message' => 'Thiếu phương thức thanh toán'], 400);
            }

            // Cập nhật điều kiện cho phép khách vãng lai thanh toán qua VNPay hoặc MoMo
            if (!$userId && !in_array($paymentMethod, ['vnpay', 'momo'])) {
                return response()->json(['message' => 'Khách vãng lai chỉ có thể thanh toán qua VNPay hoặc MoMo'], 400);
            }

            // Cập nhật danh sách phương thức thanh toán hợp lệ
            if (!in_array($paymentMethod, ['vnpay', 'cod', 'momo'])) {
                return response()->json(['message' => 'Phương thức thanh toán không hợp lệ'], 400);
            }

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
                'total_product_amount' => $totalProductAmount,
                'shipping_fee' => $shippingFee,
                'status_id' => ($paymentMethod == 'cod') ? 3 : 1, // COD: confirmed, VNPay/MoMo: pending
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

            if ($usedPoints > 0) {
                UserPointTransaction::create([
                    'user_id' => $userId,
                    'points' => -$usedPoints,
                    'type' => 'subtract',
                    'reason' => 'Sử dụng điểm để thanh toán đơn hàng : ' . $order->code,
                    'order_id' => $order->id,
                ]);
            }



            // Lưu chi tiết đơn hàng và cập nhật tồn kho
            foreach ($cartItems as $item) {
                // Tính giá bán của sản phẩm
                $product = Product::find($item['product_id']);
                $productVariant = ProductVariant::find($item['product_variant_id']);
            
                if ($productVariant) {
                    // Nếu có biến thể sản phẩm
                    $sellPrice = $productVariant->sale_price ?? $productVariant->sell_price;
                } else {
                    // Nếu không có biến thể, lấy giá sản phẩm gốc
                    $sellPrice = $product->sale_price ?? $product->sell_price;
                }
            
                // Tính tổng tiền sản phẩm theo số lượng
                $productTotal = $sellPrice * $item['quantity'];
            
                // Tính tổng tiền đơn hàng
                $totalAmount = $cartItems->sum(function ($cartItem) {
                    $product = Product::find($cartItem['product_id']);
                    $productVariant = ProductVariant::find($cartItem['product_variant_id']);
                    $sellPrice = $productVariant ? ($productVariant->sale_price ?? $productVariant->sell_price) : ($product->sale_price ?? $product->sell_price);
                    return $cartItem['quantity'] * $sellPrice;
                });
            
                // Kiểm tra và tính giá trị mã giảm giá
                $couponDiscount = $order->coupon_discount_value ?? 0;
            
                if ($order->coupon_discount_type === 'percent') {
                    // Áp dụng coupon theo phần trăm
                    $refundAmount = ($productTotal - (($couponDiscount / 100) * $productTotal)) / $item['quantity'];  // Chia theo số lượng sản phẩm
                } elseif ($order->coupon_discount_type === 'fix_amount' && $totalAmount > 0) {
                    // Áp dụng coupon cố định (theo tỷ lệ của sản phẩm trong đơn)
                    $productRatio = $productTotal / $totalAmount; // Tỷ lệ của sản phẩm so với tổng tiền đơn hàng
                    $refundAmount = ($productTotal - ($productRatio * $couponDiscount)) / $item['quantity']; // Chia theo số lượng sản phẩm
                } else {
                    // Không có coupon, số tiền hoàn trả là giá gốc
                    $refundAmount = $productTotal / $item['quantity']; // Chia theo số lượng sản phẩm
                }  
               
                $productRatio = $productTotal / $totalProductAmount;
                $pointsUsed = $order->used_points ?? 0;
                $pointsValue = 1; 
                $pointsRefundAmount = ($pointsUsed * $productRatio) * $pointsValue;
                $refundAmount -= $pointsRefundAmount;
            
                
                // Lưu chi tiết đơn hàng vào bảng order_items với giá hoàn trả
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'sell_price' => $sellPrice,
                    'refund_amount' => $refundAmount,  // Lưu số tiền hoàn trả vào cột refund_amount
                ]);
            
                // Trừ stock nếu thanh toán qua COD
                if ($paymentMethod == 'cod') {
                    if ($item['product_variant_id']) {
                        ProductVariant::where('id', $item['product_variant_id'])->decrement('stock', $item['quantity']);
                    } else {
                        Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
                    }
                }
            }
            
            
            


            // Xóa giỏ hàng sau khi đặt hàng thành công
            if ($userId) {
                CartItem::where('user_id', $userId)->delete();
            }

            // Xử lý các phương thức thanh toán
            if ($paymentMethod == 'vnpay') {
                DB::commit();
                $vnpayController = app()->make(VNPayController::class);
                return $vnpayController->createPayment(new Request([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod
                ]));
            } elseif ($paymentMethod == 'momo') {
                DB::commit();
                $momoController = app()->make(MomoController::class);
                return $momoController->momo_payment(new Request([
                    'order_id' => $order->id, // Truyền mã đơn hàng thay vì ID
                    'total_momo' => $order->total_amount, // Tổng tiền
                ]));
            } else { // COD
                $order->update(['status_id' => 3]);
                DB::commit();
                return response()->json(['message' => 'Đặt hàng thành công!', 'order' => $order], 201);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }

    public function retryPayment($orderId, Request $request)
    {
        // Lấy đơn hàng theo ID và kiểm tra trạng thái
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }

        if ($order->status_id != 1) {
            return response()->json(['message' => 'Đơn hàng không thể thanh toán lại, trạng thái không hợp lệ'], 400);
        }

        // Lấy phương thức thanh toán từ yêu cầu
        $paymentMethod = $request->input('payment_method');

        // Kiểm tra nếu phương thức thanh toán là VNPay
        if ($paymentMethod == 'vnpay') {
            // Tạo yêu cầu thanh toán VNPay
            $vnpayController = app()->make(VNPayController::class);

            return $vnpayController->createPayment(new Request([
                'order_id' => $order->id,
                'payment_method' => 'vnpay' // Giữ nguyên VNPay
            ]));
        }

        // Kiểm tra nếu phương thức thanh toán là MoMo
        if ($paymentMethod === 'momo') {
            $amount = (int) $order->total_amount;
            $momoController = app()->make(MomoController::class);
                return $momoController->momo_payment(new Request([
                    'order_id' => $order->id, // Truyền mã đơn hàng thay vì ID
                    'total_momo' => $amount, // Tổng tiền
                ]));
        }
        

        return response()->json(['message' => 'Phương thức thanh toán không hợp lệ'], 400);
    }
}
