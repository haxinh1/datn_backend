<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    public function index()
{
    if (Auth::check()) {
        // Lấy giỏ hàng từ database cho user đã đăng nhập
        $cartItems = CartItem::where('user_id', Auth::id())->with('product')->get();
    } else {
        // Lấy giỏ hàng từ session
        $cartItems = session()->get('cart', []);
    }

    return response()->json([
        'cart_items' => $cartItems,
        'session_cart' => session()->get('cart'),
        'session_driver' => config('session.driver') // Kiểm tra session đang dùng
    ]);
}



public function store(Request $request, $productId)
{
    $product = Product::findOrFail($productId);

    // Kiểm tra số lượng hợp lệ
    $request->validate([
        'quantity' => 'required|integer|min:1|max:' . $product->stock,
    ]);

    if (Auth::check()) {
        // Người dùng đã đăng nhập -> lưu vào database
        $cartItem = CartItem::where('user_id', Auth::id())->where('product_id', $productId)->first();

        if ($cartItem) {
            $cartItem->update(['quantity' => $cartItem->quantity + $request->quantity]);
        } else {
            CartItem::create([
                'user_id' => Auth::id(),
                'product_id' => $productId,
                'quantity' => $request->quantity
            ]);
        }
    } else {
        // Người dùng chưa đăng nhập -> lưu vào session
        $cart = session()->get('cart', []);

        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] += $request->quantity;
        } else {
            $cart[$productId] = [
                'product_id' => $productId,
                'quantity' => $request->quantity
            ];
        }

        session()->put('cart', $cart);
    }

    return response()->json([
        'message' => 'Sản phẩm đã thêm vào giỏ hàng',
        'session_cart' => session()->get('cart')
    ]);
}




public function update(Request $request, $productId)
{
    $product = Product::findOrFail($productId);

    // Kiểm tra số lượng hợp lệ
    $request->validate([
        'quantity' => 'required|integer|min:1|max:' . $product->stock,
    ]);

    if (Auth::check()) {
        $cartItem = CartItem::where('user_id', Auth::id())->where('product_id', $productId)->first();
        if ($cartItem) {
            $cartItem->update(['quantity' => $request->quantity]);
        }
    } else {
        $cart = session()->get('cart', []);
        if (isset($cart[$productId])) {
            $cart[$productId]['quantity'] = $request->quantity;
            session()->put('cart', $cart);
        }
    }

    return response()->json(['message' => 'Cập nhật số lượng thành công']);
}


public function destroy($productId)
{
    if (Auth::check()) {
        CartItem::where('user_id', Auth::id())->where('product_id', $productId)->delete();
    } else {
        $cart = session()->get('cart', []);
        unset($cart[$productId]); // Xóa sản phẩm khỏi session
        session()->put('cart', $cart);
    }

    return response()->json([
        'message' => 'Sản phẩm đã được xóa',
        'session_cart' => session()->get('cart')
    ]);
}

}
