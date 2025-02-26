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
use Laravel\Sanctum\PersonalAccessToken;


class CartItemController extends Controller
{
    /**
     * 📌 Lấy danh sách giỏ hàng của khách hoặc user
     */
    public function index()
{
    $sessionId = $this->getSessionId();
    $userId = Auth::id();

    if ($userId) {
        $cartItems = CartItem::where('user_id', $userId)->with(['product', 'productVariant'])->get();
    } else {
        $cartItems = CartItem::where('session_id', $sessionId)->with(['product', 'productVariant'])->get();
    }

    return response()->json([
        'cart_items' => $cartItems,
        'session_id' => $sessionId
    ]);
}


public function store(Request $request, $productId)
{
    try {
        // 🟢 Xác thực user
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Bạn chưa đăng nhập hoặc token không hợp lệ'], 401);
        }

        // 🟢 Lấy thông tin sản phẩm
        $product = Product::findOrFail($productId);
        $productVariantId = $request->input('product_variant_id', null);
        $quantity = $request->input('quantity', 1);

        Log::info('🛒 Bắt đầu thêm sản phẩm vào giỏ hàng:', [
            'user_id' => $userId,
            'product_id' => $productId,
            'product_variant_id' => $productVariantId,
            'quantity' => $quantity
        ]);

        // 🛑 Kiểm tra sản phẩm có đang bị ẩn không
        if (!$product->is_active) {
            return response()->json(['message' => 'Sản phẩm này hiện không có sẵn'], 400);
        }

        // 🔹 Nếu có `product_variant_id`, kiểm tra biến thể hợp lệ
        if ($productVariantId) {
            $productVariant = ProductVariant::where('product_id', $productId)->find($productVariantId);
            if (!$productVariant || !$productVariant->is_active) {
                return response()->json(['message' => 'Biến thể sản phẩm không hợp lệ hoặc đang bị ẩn'], 400);
            }
        }

        // 🔹 Xác định giá sản phẩm **ưu tiên `sale_price` nếu có**
        $price = $productVariantId
            ? ($productVariant->sale_price ?? $productVariant->sell_price) // Nếu có biến thể, ưu tiên sale_price
            : ($product->sale_price ?? $product->sell_price); // Nếu không có biến thể, ưu tiên sale_price

        // 🔹 Kiểm tra tồn kho
        $maxStock = $productVariantId ? $productVariant->stock : $product->stock;
        if ($quantity > $maxStock) {
            return response()->json(['message' => 'Số lượng vượt quá tồn kho'], 400);
        }

        // 🟢 Kiểm tra sản phẩm đã tồn tại trong giỏ hàng chưa
        $cartItem = CartItem::where('user_id', $userId)
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId)
            ->first();

        if ($cartItem) {
            // Nếu sản phẩm đã có trong giỏ hàng, cập nhật số lượng
            $cartItem->increment('quantity', $quantity);
            Log::info('🔄 Cập nhật số lượng giỏ hàng:', ['cart_item' => $cartItem]);
        } else {
            // Nếu chưa có, thêm mới vào giỏ hàng
            $cartItem = CartItem::create([
                'user_id' => $userId,
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'price' => $price
            ]);
            Log::info('✅ Sản phẩm mới đã thêm vào giỏ hàng:', ['cart_item' => $cartItem]);
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

        $productVariantId = $request->input('product_variant_id', null);
        $sessionId = $this->getSessionId();
        $userId = Auth::id();

        $cartQuery = CartItem::where('product_id', $productId)
                             ->where('product_variant_id', $productVariantId);

        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $cartItem = $cartQuery->first();

        if ($cartItem) {
            $cartItem->update(['quantity' => $request->quantity]);
        }

        return response()->json(['message' => 'Cập nhật số lượng thành công']);
    }

    /**
     * 📌 Xóa sản phẩm khỏi giỏ hàng
     */
    public function destroy(Request $request, $productId)
    {
        $productVariantId = $request->input('product_variant_id', null);
        $sessionId = $this->getSessionId();
        $userId = Auth::id();

        $cartQuery = CartItem::where('product_id', $productId)
                             ->where('product_variant_id', $productVariantId);

        if ($userId) {
            $cartQuery->where('user_id', $userId);
        } else {
            $cartQuery->where('session_id', $sessionId);
        }

        $cartQuery->delete();

        return response()->json(['message' => 'Sản phẩm đã được xóa']);
    }

    /**
     * 📌 Lấy session ID duy nhất cho khách vãng lai
     */
    private function getSessionId()
    {
        if (!session()->has('guest_session_id')) {
            session()->put('guest_session_id', Str::uuid()->toString()); 
        }
        return session()->get('guest_session_id');
    }
}
