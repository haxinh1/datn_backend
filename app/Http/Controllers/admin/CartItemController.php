<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class CartItemController extends Controller
{
    /**
     * 📌 Lấy danh sách giỏ hàng của khách hoặc user
     */
    public function index(Request $request)
    {
        $userId = Auth::id();
        $sessionId = session()->get('guest_session_id');

        Log::info('🔍 Kiểm tra giỏ hàng:', [
            'Auth ID' => $userId,
            'Session ID' => $sessionId
        ]);

        // ✅ Nếu user đã đăng nhập & có session, hợp nhất giỏ hàng
        if ($userId && $sessionId) {
            $this->mergeSessionCartToUser($userId, $sessionId);
        }

        // ✅ Nếu có user_id, lấy giỏ hàng theo user_id
        if ($userId) {
            $cartItems = CartItem::where('user_id', $userId)
                ->with(['product', 'productVariant'])
                ->get();

            return response()->json([
                'cart_items' => $cartItems,
                'user_id' => $userId
            ]);
        }

        // ✅ Nếu chưa đăng nhập, lấy giỏ hàng theo session_id
        if ($sessionId) {
            $cartItems = CartItem::where('session_id', $sessionId)
                ->with(['product', 'productVariant'])
                ->get();

            return response()->json([
                'cart_items' => $cartItems,
                'session_id' => $sessionId
            ]);
        }

        return response()->json([
            'cart_items' => [],
            'message' => 'Không tìm thấy giỏ hàng'
        ], 404);
    }

    /**
     * 📌 Thêm sản phẩm vào giỏ hàng
     */
    public function store(Request $request, $productId)
{
    try {
        $user = Auth::guard('sanctum')->user(); // ✅ Kiểm tra user từ Sanctum
        $userId = $user ? $user->id : null;
        $sessionId = $userId ? null : session()->get('guest_session_id');

        // Nếu user đã đăng nhập nhưng session cart chưa hợp nhất, hợp nhất ngay
        if ($userId && $sessionId) {
            $this->mergeSessionCartToUser($userId, $sessionId);
        }

        Log::info('📌 Kiểm tra trước khi thêm vào giỏ hàng:', [
            'Auth ID' => $userId,
            'Session ID' => $sessionId
        ]);

        // 🛒 Lấy thông tin sản phẩm
        $product = Product::findOrFail($productId);
        $quantity = $request->input('quantity', 1);

        // ✅ Kiểm tra sản phẩm đã có trong giỏ hàng chưa
        $cartQuery = CartItem::where('product_id', $productId);

        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $cartItem = $cartQuery->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $quantity);
        } else {
            $cartItem = CartItem::create([
                'user_id' => $userId,
                'session_id' => $userId ? null : $sessionId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'price' => $product->sale_price ?? $product->sell_price
            ]);
        }

        return response()->json([
            'message' => 'Sản phẩm đã thêm vào giỏ hàng',
            'cart_item' => $cartItem
        ]);
    } catch (\Exception $e) {
        return response()->json(['message' => 'Lỗi khi thêm sản phẩm vào giỏ hàng'], 500);
    }
}


    /**
     * 📌 Hợp nhất giỏ hàng session vào user khi đăng nhập
     */
    private function mergeSessionCartToUser($userId, $sessionId)
    {
        if (!$sessionId) {
            return;
        }

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
