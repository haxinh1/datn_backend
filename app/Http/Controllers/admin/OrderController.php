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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    public function getOrderByCode($orderCode)
    {
        $order = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->where('code', $orderCode)
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Không tìm thấy đơn hàng với mã ' . $orderCode
            ], 404);
        }

        return response()->json([
            'order' => $order
        ], 200);
    }

    public function completedOrders(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->whereIn('status_id', [7, 11])
            ->orderBy('created_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng hoàn thành.'], 404);
        }

        return response()->json(['orders' => $orders], 200);
    }

    public function acceptedReturnOrders(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->where('status_id', 10)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($orders->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng được chấp nhận trả lại.'], 404);
        }

        return response()->json(['orders' => $orders], 200);
    }

    public function show($id)
    {
        $order = Order::with(['orderItems.product', 'orderItems.productVariant', 'payment', 'status', 'orderStatuses'])
            ->where('id', $id)
            ->orderBy('created_at', 'desc')
            ->first();
        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }
        return response()->json(['order' => $order], 200);
    }

    /**
     * Đặt hàng (Thanh toán COD hoặc chuyển khoản)
     */
    public function store(Request $request)
    {
        Log::info('DEBUG - Dữ liệu yêu cầu khi đặt hàng:', ['request' => $request->all()]);

        DB::beginTransaction();

        try {
            $userId = $request->input('user_id') ?? null;
            $user = $userId ? User::find($userId) : null;

            // Lấy danh sách sản phẩm từ yêu cầu (chỉ các sản phẩm được chọn)
            $selectedProducts = collect($request->input('products', []));

            // Kiểm tra nếu không có sản phẩm được chọn
            if ($selectedProducts->isEmpty()) {
                return response()->json(['message' => 'Không có sản phẩm nào được chọn'], 400);
            }

            // Xác thực sản phẩm và kiểm tra tồn kho
            foreach ($selectedProducts as $item) {
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
            $now = now();
            $totalAmount = $selectedProducts->sum(function ($item) use ($now) {
                $product = Product::find($item['product_id']);
                $price = 0;

                if (!empty($item['product_variant_id'])) {
                    $productVariant = ProductVariant::find($item['product_variant_id']);
                    if (
                        $productVariant &&
                        $productVariant->sale_price !== null &&
                        $productVariant->sale_price_start_at &&
                        $productVariant->sale_price_end_at &&
                        $now->between($productVariant->sale_price_start_at, $productVariant->sale_price_end_at)
                    ) {
                        $price = $productVariant->sale_price;
                    } else {
                        $price = $productVariant->sell_price ?? 0;
                    }
                } else {
                    if (
                        $product &&
                        $product->sale_price !== null &&
                        $product->sale_price_start_at &&
                        $product->sale_price_end_at &&
                        $now->between($product->sale_price_start_at, $product->sale_price_end_at)
                    ) {
                        $price = $product->sale_price;
                    } else {
                        $price = $product->sell_price ?? 0;
                    }
                }

                return $item['quantity'] * $price;
            });

            // Kiểm tra và áp dụng mã giảm giá
            $couponCode = $request->input('coupon_code');
            $discountAmount = 0;
            $coupon = null;
            if ($couponCode) {
                $coupon = Coupon::where('code', $couponCode)
                    ->where('is_active', true)
                    ->where('start_date', '<=', now())
                    ->where('end_date', '>=', now())
                    ->first();

                if (!$coupon) {
                    return response()->json(['message' => 'Mã giảm giá không hợp lệ hoặc đã hết hạn'], 400);
                }

                if ($coupon->usage_limit !== null && $coupon->usage_limit <= 0) {
                    return response()->json(['message' => 'Mã giảm giá đã hết lượt sử dụng'], 400);
                }

                if ($coupon->discount_type == 'percent') {
                    $discountAmount = ($coupon->discount_value / 100) * $totalAmount;
                } else {
                    $discountAmount = $coupon->discount_value;
                }

                if ($discountAmount > $totalAmount) {
                    $discountAmount = $totalAmount;
                }

                Log::info("DEBUG - Áp dụng mã giảm giá: {$couponCode}, giảm giá: $discountAmount, tổng tiền sau giảm: $totalAmount");
            }

            // Kiểm tra phí vận chuyển
            $shippingFee = $request->input('shipping_fee', 0);
            if ($shippingFee < 0) {
                return response()->json(['message' => 'Phí vận chuyển không hợp lệ'], 400);
            }

            // Kiểm tra điểm tiêu dùng
            $usedPoints = $request->input('used_points', 0);
            $discountPoints = 0;
            if ($userId) {
                if ($usedPoints < 0) {
                    return response()->json(['message' => 'Số điểm sử dụng không hợp lệ'], 400);
                }
                if ($usedPoints > $user->loyalty_points) {
                    return response()->json(['message' => 'Số điểm không đủ'], 400);
                }
                $discountPoints = $usedPoints;
            }

            // Tính tổng tiền sau khi áp dụng giảm giá và điểm
            $totalAmount = $totalAmount - $discountAmount + $shippingFee - $discountPoints;
            if ($totalAmount < 0) {
                $totalAmount = 0;
            }

            // Tổng tiền sản phẩm
            $totalProductAmount = $totalAmount + $discountAmount + $usedPoints - $shippingFee;

            Log::info('DEBUG - Tổng tiền sau khi áp dụng giảm giá và điểm thưởng:', [
                'subtotal' => $totalProductAmount,
                'discount_amount' => $discountAmount,
                'used_points' => $usedPoints,
                'shipping_fee' => $shippingFee,
                'total_amount' => $totalAmount
            ]);

            // Trừ điểm tiêu dùng
            if ($userId && $usedPoints > 0) {
                $user->decrement('loyalty_points', $usedPoints);
                $updatedPoints = $user->fresh()->loyalty_points;

                Log::info('DEBUG - Trừ điểm thành công:', [
                    'user_id' => $userId,
                    'used_points' => $usedPoints,
                    'remaining_points' => $updatedPoints
                ]);
            }

            // Xác thực thông tin người dùng
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

            if (!$userId && !in_array($paymentMethod, ['vnpay', 'momo'])) {
                return response()->json(['message' => 'Khách vãng lai chỉ có thể thanh toán qua VNPay hoặc MoMo'], 400);
            }

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
                'status_id' => ($paymentMethod == 'cod') ? 3 : 1,
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

            if ($coupon) {
                if ($coupon->usage_limit !== null && $coupon->usage_limit > 0) {
                    $coupon->decrement('usage_limit');
                }
                $coupon->increment('usage_count');
            }

            if ($usedPoints > 0) {
                UserPointTransaction::create([
                    'user_id' => $userId,
                    'points' => -$usedPoints,
                    'type' => 'subtract',
                    'reason' => 'Sử dụng điểm để thanh toán đơn hàng: ' . $order->code,
                    'order_id' => $order->id,
                ]);
            }

            // Lưu chi tiết đơn hàng và cập nhật tồn kho
            $orderItems = [];
            $now = now();
            foreach ($selectedProducts as $item) {
                $product = Product::find($item['product_id']);
                $productVariant = $item['product_variant_id'] ? ProductVariant::find($item['product_variant_id']) : null;

                // Giá bán thực tế
                if ($productVariant) {
                    $sellPrice = (
                        $productVariant->sale_price &&
                        $productVariant->sale_price_start_at &&
                        $productVariant->sale_price_end_at &&
                        $now->between($productVariant->sale_price_start_at, $productVariant->sale_price_end_at)
                    ) ? $productVariant->sale_price : $productVariant->sell_price;
                } else {
                    $sellPrice = (
                        $product->sale_price &&
                        $product->sale_price_start_at &&
                        $product->sale_price_end_at &&
                        $now->between($product->sale_price_start_at, $product->sale_price_end_at)
                    ) ? $product->sale_price : $product->sell_price;
                }

                // Tổng tiền sản phẩm theo số lượng
                $productTotal = $sellPrice * $item['quantity'];

                // Tính tỉ lệ sản phẩm trong đơn
                $productRatio = $totalProductAmount ? ($productTotal / $totalProductAmount) : 0;

                // Tính giảm giá theo coupon
                $couponDiscount = 0;
                if ($order->coupon_discount_type === 'percent') {
                    $couponDiscount = ($order->coupon_discount_value / 100) * $productTotal;
                } elseif ($order->coupon_discount_type === 'fix_amount') {
                    $couponDiscount = $productRatio * $order->coupon_discount_value;
                }

                // Tính giảm giá theo điểm
                $pointsDiscount = $order->used_points ? ($order->used_points * $productRatio) : 0;

                // Tính refund_amount
                $refundAmount = ($productTotal - $couponDiscount - $pointsDiscount) / $item['quantity'];
                if ($refundAmount < 0) {
                    $refundAmount = 0;
                }

                // Tạo mục đơn hàng
                $orderItems[] = [
                    'order_id' => $order->id,
                    'product_id' => $item['product_id'],
                    'product_variant_id' => $item['product_variant_id'] ?? null,
                    'quantity' => $item['quantity'],
                    'sell_price' => $sellPrice,
                    'refund_amount' => round($refundAmount, 2),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Cập nhật tổng số lượng bán
                $product->total_sales += $item['quantity'];
                $product->save();

                // Trừ kho nếu thanh toán qua COD
                if ($paymentMethod == 'cod') {
                    if ($item['product_variant_id']) {
                        ProductVariant::where('id', $item['product_variant_id'])->decrement('stock', $item['quantity']);
                    } else {
                        Product::where('id', $item['product_id'])->decrement('stock', $item['quantity']);
                    }
                }
            }

            // Bulk insert OrderItem
            OrderItem::insert($orderItems);

            // Xóa chỉ các sản phẩm được chọn khỏi giỏ hàng
            if ($userId) {
                foreach ($selectedProducts as $item) {
                    if (isset($item['id']) && $item['id']) {
                        CartItem::where('id', $item['id'])->delete();
                    }
                }
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
                    'order_id' => $order->id,
                    'total_momo' => $order->total_amount,
                ]));
            } else { // COD
                $order->update(['status_id' => 3]);
                DB::commit();
                return response()->json(['message' => 'Đặt hàng thành công!', 'order' => $order], 201);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Lỗi khi đặt hàng:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }

    public function retryPayment($orderId, Request $request)
    {
        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Đơn hàng không tồn tại'], 404);
        }

        if ($order->status_id != 1) {
            return response()->json(['message' => 'Đơn hàng không thể thanh toán lại, trạng thái không hợp lệ'], 400);
        }

        $paymentMethod = $request->input('payment_method');

        if ($paymentMethod == 'vnpay') {
            $vnpayController = app()->make(VNPayController::class);
            return $vnpayController->createPayment(new Request([
                'order_id' => $order->id,
                'payment_method' => 'vnpay'
            ]));
        }

        if ($paymentMethod === 'momo') {
            $amount = (int) $order->total_amount;
            $momoController = app()->make(MomoController::class);
            return $momoController->momo_payment(new Request([
                'order_id' => $order->id,
                'total_momo' => $amount,
            ]));
        }

        return response()->json(['message' => 'Phương thức thanh toán không hợp lệ'], 400);
    }
}