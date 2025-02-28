<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
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
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;
        $sessionId = session()->get('guest_session_id');

        Log::info('🔍 Lấy giỏ hàng:', [
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
                ->with(['product', 'productVariant']) // Gọi cả productVariant
                ->get();

            return response()->json([
                'cart_items' => $cartItems,
                'user_id' => $userId
            ]);
        }

        // ✅ Nếu chưa đăng nhập, lấy giỏ hàng theo session_id
        if ($sessionId) {
            $cartItems = CartItem::where('session_id', $sessionId)
                ->with(['product', 'productVariant']) // Gọi cả productVariant
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
    /**
     * 📌 Thêm sản phẩm vào giỏ hàng (Hỗ trợ cả khách vãng lai & user đăng nhập)
     */
    public function store(Request $request, $productId)
    {
        try {
            $user = Auth::guard('sanctum')->user();
            $userId = $user ? $user->id : null;
            $sessionId = $userId ? null : $this->getSessionId();
            $productVariantId = $request->input('product_variant_id', null);
            $quantity = $request->input('quantity', 1);

            Log::info('📌 Kiểm tra trước khi thêm vào giỏ hàng:', [
                'Auth ID' => $userId,
                'Session ID' => $sessionId,
                'Product ID' => $productId,
                'Product Variant ID' => $productVariantId
            ]);

            // 🛒 Lấy thông tin sản phẩm hoặc biến thể
            if ($productVariantId) {
                $productVariant = ProductVariant::where('product_id', $productId)->findOrFail($productVariantId);
                $price = $productVariant->sale_price ?? $productVariant->sell_price;
            } else {
                $product = Product::findOrFail($productId);
                $price = $product->sale_price ?? $product->sell_price;
            }

            // ✅ Kiểm tra sản phẩm đã có trong giỏ hàng chưa
            $cartQuery = CartItem::where('product_id', $productId)
                ->where('product_variant_id', $productVariantId);

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
                    'product_variant_id' => $productVariantId,
                    'quantity' => $quantity,
                    'price' => $price
                ]);
            }

            return response()->json([
                'message' => 'Sản phẩm đã thêm vào giỏ hàng',
                'cart_item' => $cartItem
            ]);
        } catch (\Exception $e) {
            Log::error('❌ Lỗi khi thêm sản phẩm vào giỏ hàng:', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Lỗi khi thêm sản phẩm vào giỏ hàng'], 500);
        }
    }


    /**
     * 📌 Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function update(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;
        $sessionId = $userId ? null : session()->get('guest_session_id');

        $cartQuery = CartItem::where('product_id', $productId);

        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $cartItem = $cartQuery->first();

        if ($cartItem) {
            $cartItem->update(['quantity' => $request->quantity]);
            return response()->json(['message' => 'Cập nhật số lượng thành công']);
        }

        return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
    }

    /**
     * 📌 Xóa sản phẩm khỏi giỏ hàng
     */
    public function destroy(Request $request, $productId)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;
        $sessionId = $userId ? null : session()->get('guest_session_id');

        $cartQuery = CartItem::where('product_id', $productId);

        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $deleted = $cartQuery->delete();

        if ($deleted) {
            return response()->json(['message' => 'Sản phẩm đã được xóa']);
        }

        return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
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

    /**
     * 📌 Lấy session ID duy nhất cho khách vãng lai
     */
    private function getSessionId()
    {
        if (!session()->has('guest_session_id')) {
            $sessionId = Str::uuid()->toString();
            session()->put('guest_session_id', $sessionId);
            session()->save();
        }
        return session()->get('guest_session_id');
    }
}
