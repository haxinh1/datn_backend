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


class OrderController extends Controller
{
    /**
     * Lấy danh sách đơn hàng
     */
    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'payment', 'status', 'orderStatuses'])
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json(['orders' => $orders], 200);
    }

    /**
     * Đặt hàng (Thanh toán COD hoặc chuyển khoản)
     */
    public function store(Request $request)
    {


        DB::beginTransaction();

        try {
            // Lấy user từ token để đảm bảo đăng nhập
            $user = Auth::guard('sanctum')->user();
            $userId = $user ? $user->id : null;

            // Nếu user đăng nhập, lấy giỏ hàng từ database
            if ($userId) {
                $cartItems = CartItem::where('user_id', $userId)->with('product', 'productVariant')->get();
            } else {
                // Nếu khách vãng lai, lấy giỏ hàng từ request
                $cartItems = collect($request->input('cart_items', []));
            }
            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Giỏ hàng trống'], 400);
            }
            $cartItems = $cartItems->map(function ($item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Sản phẩm ID {$item['product_id']} không tồn tại");
                }
                return $item;
            });

            $totalAmount = $cartItems->sum(function ($item) {
                $product = Product::find($item['product_id']);
                if (!$product) {
                    throw new \Exception("Sản phẩm ID {$item['product_id']} không tồn tại");
                }

                if (!empty($item['product_variant_id'])) {
                    $productVariant = ProductVariant::where('product_id', $item['product_id'])
                        ->find($item['product_variant_id']);
                    $price = $productVariant ? ($productVariant->sale_price ?? $productVariant->sell_price) : 0;
                } else {
                    $price = $product->sale_price ?? $product->sell_price;
                }

                return $item['quantity'] * $price;
            });


            if ($totalAmount <= 0) {
                return response()->json(['message' => 'Giá trị đơn hàng không hợp lệ'], 400);
            }

            // Kiểm tra thông tin khách hàng nếu chưa đăng nhập
            $request->validate([
                'fullname' => $user ? 'nullable' : 'required|string|max:255',
                'email' => $user ? 'nullable' : 'required|email|max:255',
                'phone_number' => $user ? 'nullable' : 'required|string|max:20',
                'address' => $user ? 'nullable' : 'required|string|max:255',
            ]);


            // Lấy thông tin khách hàng
            $fullname = $user->fullname ?? $request->fullname;
            $email = $user->email ?? $request->email;
            $phone_number = $user->phone_number ?? $request->phone_number;

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

            // Tạo đơn hàng
            $order = Order::create([
                'code' => 'ORD' . strtoupper(Str::random(8)),
                'user_id' => $userId,
                'fullname' => $fullname,
                'email' => $email,
                'phone_number' => $phone_number,
                'address' => $address, // Lấy từ bảng user_addresses
                'total_amount' => $totalAmount,
                'status_id' => ($paymentMethod == 'vnpay') ? 1 : 3, // VNPay = 1, COD = 3
                'payment_id' => $paymentId,
            ]);

            // Lưu chi tiết đơn hàng và cập nhật tồn kho
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id ?? null,
                    'quantity' => $item->quantity,
                    'sell_price' => $item->product_variant_id
                        ? ($item->productVariant->sale_price ?? $item->productVariant->sell_price)
                        : ($item->product->sale_price ?? $item->product->sell_price),
                ]);
            }

            // Xóa giỏ hàng sau khi đặt hàng
            CartItem::where('user_id', $userId)->delete();

            // Nếu chọn VNPay, gọi VNPayController để tạo URL thanh toán
            if ($paymentMethod == 'vnpay') {
                DB::commit(); // Commit trước khi gọi VNPay (tránh mất đơn hàng nếu lỗi)

                $vnpayController = app()->make(VNPayController::class);
                return $vnpayController->createPayment(new Request([
                    'order_id' => $order->id,
                    'payment_method' => $paymentMethod // 
                ]));
            }

            // Nếu chọn COD, đơn hàng được xác nhận ngay lập tức
            $order->update(['status_id' => 3]); // "Chờ xử lý"

            // Chỉ trừ stock nếu chọn COD
            if ($paymentMethod == 'cod') {
                foreach ($cartItems as $item) {
                    if ($item->product_variant_id) {
                        ProductVariant::where('id', $item->product_variant_id)->decrement('stock', $item->quantity);
                    } else {
                        Product::where('id', $item->product_id)->decrement('stock', $item->quantity);
                    }
                }
            }

            DB::commit();

            return response()->json(['message' => 'Đặt hàng thành công!', 'order' => $order], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }
}
