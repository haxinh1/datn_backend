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
        DB::beginTransaction();

        try {
            // ✅ Lấy user từ token để đảm bảo đăng nhập
            $user = Auth::guard('sanctum')->user();
            $userId = $user ? $user->id : null;
            $sessionId = session()->get('guest_session_id');

            Log::info('🛒 Bắt đầu đặt hàng', [
                'Auth ID' => Auth::id(),
                'Sanctum User ID' => $userId,
                'Session ID' => $sessionId
            ]);

            // ✅ Nếu user đăng nhập nhưng vẫn còn session cart, hợp nhất vào tài khoản
            if ($userId && $sessionId) {
                $this->mergeSessionCartToUser($userId, $sessionId);
            }

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
                'code' => 'ORD' . strtoupper(Str::random(8)),
                'user_id' => $userId, // ✅ Đảm bảo user_id đúng
                'session_id' => $userId ? null : $sessionId,
                'fullname' => $request->fullname,
                'email' => $request->email,
                'phone_number' => $request->phone_number,
                'address' => $request->address,
                'total_amount' => $totalAmount,
                'status_id' => 1,
                'payment_id' => $request->payment_id ?? null,
            ]);

            // ✅ Lưu chi tiết đơn hàng
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

            // ✅ Xóa giỏ hàng sau khi đặt hàng thành công
            CartItem::where('user_id', $userId)
                ->orWhere('session_id', $sessionId)
                ->delete();

            session()->forget('guest_session_id');

            DB::commit();

            return response()->json([
                'message' => 'Đặt hàng thành công!',
                'order' => $order
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
    private function mergeSessionCartToUser($userId, $sessionId)
    {
        Log::info('🔄 Hợp nhất giỏ hàng session vào user', [
            'user_id' => $userId,
            'session_id' => $sessionId
        ]);

        $cartItems = CartItem::where('session_id', $sessionId)->get();

        foreach ($cartItems as $cartItem) {
            $existingCartItem = CartItem::where('user_id', $userId)
                ->where('product_id', $cartItem->product_id)
                ->first();

            if ($existingCartItem) {
                $existingCartItem->increment('quantity', $cartItem->quantity);
                $cartItem->delete();
            } else {
                $cartItem->update(['user_id' => $userId, 'session_id' => null]);
            }
        }

        session()->forget('guest_session_id');
        session()->save();

        Log::info('✅ Giỏ hàng đã được hợp nhất', ['user_id' => $userId]);
    }
}
