<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    /**
     * 📌 Lấy danh sách giỏ hàng
     */
    public function index()
    {
        if (Auth::check()) {
            $cartItems = CartItem::where('user_id', Auth::id())->with(['product', 'productVariant'])->get();
        } else {
            $cartItems = session()->get('cart', []);
        }

        return response()->json([
            'cart_items' => $cartItems,
            'session_cart' => session()->get('cart'),
            'session_driver' => config('session.driver')
        ]);
    }

    /**
     * 📌 Thêm sản phẩm vào giỏ hàng (hỗ trợ biến thể)
     */
    /**
 * 📌 Thêm sản phẩm vào giỏ hàng (hỗ trợ biến thể)
 */
public function store(Request $request, $productId)
{
    $product = Product::findOrFail($productId);
    $productVariantId = $request->input('product_variant_id', null);
    $quantity = $request->input('quantity', 1);

    // 🛑 Kiểm tra sản phẩm có đang bị ẩn không
    if (!$product->is_active) {
        return response()->json(['message' => 'Sản phẩm này hiện không có sẵn'], 400);
    }

    // 🔹 Kiểm tra nếu có `product_variant_id`
    if ($productVariantId) {
        $productVariant = ProductVariant::where('product_id', $productId)->find($productVariantId);
        if (!$productVariant || !$productVariant->is_active) {
            return response()->json(['message' => 'Biến thể sản phẩm không hợp lệ hoặc đang bị ẩn'], 400);
        }
    }

    // 🔹 Xác định giá sản phẩm **ưu tiên `sale_price` nếu có**
    $price = $productVariantId
        ? ($productVariant->sale_price ?? $productVariant->sell_price) // Nếu có biến thể, ưu tiên sale_price nếu có
        : ($product->sale_price ?? $product->sell_price); // Nếu không có biến thể, ưu tiên sale_price nếu có

    // 🔹 Kiểm tra tồn kho
    $maxStock = $productVariantId ? $productVariant->stock : $product->stock;
    if ($quantity > $maxStock) {
        return response()->json(['message' => 'Số lượng vượt quá tồn kho'], 400);
    }

    // 🔹 Nếu đã đăng nhập -> Lưu vào database
    if (Auth::check()) {
        $cartItem = CartItem::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->where('product_variant_id', $productVariantId)
            ->first();

        if ($cartItem) {
            $cartItem->increment('quantity', $quantity);
        } else {
            CartItem::create([
                'user_id' => Auth::id(),
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'price' => $price // 🔹 Cập nhật giá tại thời điểm thêm vào giỏ hàng
            ]);
        }
    } else {
        // 🔹 Nếu chưa đăng nhập -> Lưu vào session
        $cart = session()->get('cart', []);
        $key = $productId . '-' . ($productVariantId ?? 'null');

        if (isset($cart[$key])) {
            $cart[$key]['quantity'] += $quantity;
        } else {
            $cart[$key] = [
                'product_id' => $productId,
                'product_variant_id' => $productVariantId,
                'quantity' => $quantity,
                'price' => $price
            ];
        }

        session()->put('cart', $cart);
    }

    return response()->json([
        'message' => 'Sản phẩm đã thêm vào giỏ hàng',
        'session_cart' => session()->get('cart')
    ]);
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

        if (Auth::check()) {
            $cartItem = CartItem::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->where('product_variant_id', $productVariantId)
                ->first();

            if ($cartItem) {
                $cartItem->update(['quantity' => $request->quantity]);
            }
        } else {
            $cart = session()->get('cart', []);
            $key = $productId . '-' . ($productVariantId ?? 'null');

            if (isset($cart[$key])) {
                $cart[$key]['quantity'] = $request->quantity;
                session()->put('cart', $cart);
            }
        }

        return response()->json(['message' => 'Cập nhật số lượng thành công']);
    }

    /**
     * 📌 Xóa sản phẩm khỏi giỏ hàng
     */
    public function destroy(Request $request, $productId)
    {
        $productVariantId = $request->input('product_variant_id', null);

        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())
                ->where('product_id', $productId)
                ->where('product_variant_id', $productVariantId)
                ->delete();
        } else {
            $cart = session()->get('cart', []);
            $key = $productId . '-' . ($productVariantId ?? 'null');

            if (isset($cart[$key])) {
                unset($cart[$key]);
                session()->put('cart', $cart);
            }
        }

        return response()->json(['message' => 'Sản phẩm đã được xóa']);
    }
}
