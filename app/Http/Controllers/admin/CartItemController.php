<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    /**
     * Lấy danh sách giỏ hàng
     */
    public function index()
    {
        if (Auth::check()) {
            $cartItems = CartItem::where('user_id', Auth::id())->with('product')->get();
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
     * Thêm sản phẩm vào giỏ hàng
     */
    public function store(Request $request, $productId)
    {
        $product = Product::findOrFail($productId);

        $request->validate([
            'quantity' => 'required|integer|min:1|max:' . $product->stock,
        ]);

        if (Auth::check()) {
            $cartItem = CartItem::where('user_id', Auth::id())->where('product_id', $productId)->first();

            if ($cartItem) {
                $cartItem->increment('quantity', $request->quantity);
            } else {
                CartItem::create([
                    'user_id' => Auth::id(),
                    'product_id' => $productId,
                    'quantity' => $request->quantity
                ]);
            }
        } else {
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

    /**
     * Cập nhật số lượng sản phẩm trong giỏ hàng
     */
    public function update(Request $request, $productId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
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

    /**
     * Xóa sản phẩm khỏi giỏ hàng
     */
    public function destroy($productId)
    {
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->where('product_id', $productId)->delete();
        } else {
            $cart = session()->get('cart', []);
            unset($cart[$productId]);
            session()->put('cart', $cart);
        }

        return response()->json(['message' => 'Sản phẩm đã được xóa']);
    }
}
