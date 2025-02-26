<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderOrderStatus;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class OrderController extends Controller
{
    /**
     * 📌 Lấy danh sách đơn hàng
     */
    public function index(Request $request)
    {
        $orders = Order::with(['orderItems.product', 'payment', 'status', 'orderStatuses'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json(['orders' => $orders], 200);
    }

    /**
     * 📌 Đặt hàng (Thanh toán COD hoặc chuyển khoản)
     */
    public function store(Request $request)
    {
        DB::beginTransaction(); // Bắt đầu transaction

        try {
            // ✅ Lấy user_id hoặc session_id
            $userId = Auth::id();
            $sessionId = session()->get('guest_session_id');

            if (!$userId && !$sessionId) {
                return response()->json(['message' => 'Không thể xác định khách hàng'], 400);
            }

            Log::info('🛒 Bắt đầu đặt hàng', ['user_id' => $userId, 'session_id' => $sessionId]);

            // ✅ Lấy giỏ hàng theo user hoặc session
            $cartItems = CartItem::where(function ($query) use ($userId, $sessionId) {
                if ($userId) {
                    $query->where('user_id', $userId);
                } else {
                    $query->where('session_id', $sessionId);
                }
            })->with('product', 'productVariant')->get();

            if ($cartItems->isEmpty()) {
                return response()->json(['message' => 'Giỏ hàng trống'], 400);
            }

            // ✅ Tính tổng tiền đơn hàng
            $totalAmount = $cartItems->sum(function ($item) {
                return $item->quantity * ($item->product_variant_id
                    ? ($item->productVariant->sale_price ?? $item->productVariant->sell_price)
                    : ($item->product->sale_price ?? $item->product->sell_price));
            });

            if ($totalAmount <= 0) {
                return response()->json(['message' => 'Giá trị đơn hàng không hợp lệ'], 400);
            }

            // ✅ Tạo đơn hàng
            $order = Order::create([
                'code' => 'ORD' . strtoupper(Str::random(8)), // Mã đơn ngẫu nhiên
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'fullname' => $request->fullname,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'total_amount' => $totalAmount,
                'status_id' => 1, // Trạng thái "Đang xử lý"
                'payment_id' => null, // Chưa thanh toán
            ]);

            // ✅ Thêm trạng thái vào `order_order_statuses`
            OrderOrderStatus::create([
                'order_id' => $order->id,
                'order_status_id' => 1, // Trạng thái "Đang xử lý"
                'modified_by' => $userId,
                'note' => 'Đơn hàng mới được tạo.',
                'employee_evidence' => null,
            ]);

            // ✅ Thêm sản phẩm vào `order_items`
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

            // ✅ Xóa giỏ hàng sau khi đặt hàng
            CartItem::where('user_id', $userId)->orWhere('session_id', $sessionId)->delete();

            DB::commit(); // Commit transaction

            return response()->json([
                'message' => 'Đặt hàng thành công!',
                'order' => $order,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ Lỗi khi đặt hàng:', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * 📌 Lấy chi tiết đơn hàng
     */
    public function show($id)
    {
        $order = Order::with(['orderItems.product', 'payment', 'status', 'orderStatuses'])->find($id);

        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json(['order' => $order], 200);
    }
}
