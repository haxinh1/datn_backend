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

    public function index(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;
        $sessionId = session()->get('guest_session_id');

        Log::info('Lấy giỏ hàng:', [
            'Auth ID' => $userId,
            'Session ID' => $sessionId
        ]);

        //  Nếu user đã đăng nhập & có session, hợp nhất giỏ hàng
        if ($userId && $sessionId) {
            $this->mergeSessionCartToUser($userId, $sessionId);
        }

        // Nếu có user_id, lấy giỏ hàng theo user_id
        if ($userId) {
            $cartItems = CartItem::where('user_id', $userId)
                ->with(['product', 'productVariant'])
                ->get();

            return response()->json([
                'cart_items' => $cartItems,
                'user_id' => $userId
            ]);
        }

        // Nếu chưa đăng nhập, lấy giỏ hàng theo session_id
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

            /// ✅ Kiểm tra tồn kho
            if ($productVariantId) {
                $productVariant = ProductVariant::where('product_id', $productId)->findOrFail($productVariantId);
                $availableStock = $productVariant->stock;
                $price = $productVariant->sale_price ?? $productVariant->sell_price;
            } else {
                $product = Product::findOrFail($productId);
                $availableStock = $product->stock;
                $price = $product->sale_price ?? $product->sell_price;
            }

            if ($availableStock < $quantity) {
                return response()->json(['message' => 'Sản phẩm không đủ số lượng tồn kho'], 400);
            }

            // Lấy thông tin sản phẩm hoặc biến thể
            if ($productVariantId) {
                $productVariant = ProductVariant::where('product_id', $productId)->findOrFail($productVariantId);
                $price = $productVariant->sale_price ?? $productVariant->sell_price;
            } else {
                $product = Product::findOrFail($productId);
                $price = $product->sale_price ?? $product->sell_price;
            }
            // Kiểm tra tổng số lượng đã có trong giỏ hàng
            $existingCartItem = CartItem::where('product_id', $productId)
                ->where('product_variant_id', $productVariantId);

            if ($userId) {
                $existingCartItem->where('user_id', $userId);
            } else {
                $existingCartItem->where('session_id', $sessionId);
            }

            $existingCartItem = $existingCartItem->first();
            $cartQuantity = $existingCartItem ? $existingCartItem->quantity : 0;

            // Kiểm tra nếu tổng số lượng vượt quá tồn kho
            if (($cartQuantity + $quantity) > $availableStock) {
                return response()->json([
                    'message' => 'Không đủ tồn kho. Chỉ còn ' . $availableStock . ' sản phẩm.'
                ], 400);
            }

            // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
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
            //Check validate stock khi thêm số lượng sản phẩm

            return response()->json([
                'message' => 'Sản phẩm đã thêm vào giỏ hàng',
                'cart_item' => $cartItem
            ]);
        } catch (\Exception $e) {
            Log::error(' Lỗi khi thêm sản phẩm vào giỏ hàng:', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Lỗi khi thêm sản phẩm vào giỏ hàng'], 500);
        }
    }


    public function update(Request $request, $productId, $variantId = null)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;
        $sessionId = $userId ? null : session()->get('guest_session_id');

        $cartQuery = CartItem::where('product_id', $productId);

        if ($variantId && $variantId != "NULL" && $variantId != "0") {
            $cartQuery->where('product_variant_id', $variantId);
        } else {
            $cartQuery->whereNull('product_variant_id');
        }

        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $cartItem = $cartQuery->first();
        // ✅ Kiểm tra tồn kho trước khi cập nhật
        if ($variantId) {
            $stock = ProductVariant::where('id', $variantId)->value('stock');
        } else {
            $stock = Product::where('id', $productId)->value('stock');
        }

        if ($stock < $request->quantity) {
            return response()->json(['message' => 'Số lượng sản phẩm trong kho không đủ'], 400);
        }

        if ($cartItem) {
            $cartItem->update(['quantity' => $request->quantity]);
            return response()->json(['message' => 'Cập nhật số lượng thành công']);
        }

        return response()->json(['message' => 'Không tìm thấy sản phẩm trong giỏ hàng'], 404);
    }


    public function destroy(Request $request, $productId, $variantId = null)
    {
        $user = Auth::guard('sanctum')->user();
        $userId = $user ? $user->id : null;
        $sessionId = $userId ? null : session()->get('guest_session_id');

        // Tạo truy vấn giỏ hàng
        $cartQuery = CartItem::where('product_id', $productId);

        // Nếu có variantId, lọc theo biến thể
        if ($variantId) {
            $cartQuery->where('product_variant_id', $variantId);
        } else {
            $cartQuery->whereNull('product_variant_id');
        }

        // Lọc theo user hoặc session
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

        Log::info('Giỏ hàng đã được hợp nhất', ['user_id' => $userId]);
    }

    // Lấy session ID duy nhất cho khách vãng lai

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
