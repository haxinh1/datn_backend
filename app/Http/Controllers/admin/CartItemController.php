<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\CartItem;
use App\Http\Requests\StoreCartItemRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CartItemController extends Controller
{
    public function index()
    {
        if (Auth::check()) {
            $cartItems = CartItem::where('user_id', Auth::id())->with(['product', 'productVariant'])->get();
        } else {
            $cartItems = session()->get('cart', []);
        }

        return response()->json($cartItems);
    }

    public function store(Request $request, $productId)
    {
        if (Auth::check()) {
            CartItem::updateOrCreate(
                ['user_id' => Auth::id(), 'product_id' => $productId],
                ['quantity' => $request->quantity ?? 1]
            );
        } else {
            $cart = session()->get('cart', []);
            if (isset($cart[$productId])) {
                $cart[$productId]['quantity'] += $request->quantity ?? 1;
            } else {
                $cart[$productId] = [
                    'product_id' => $productId,
                    'quantity' => $request->quantity ?? 1
                ];
            }
            session()->put('cart', $cart);
        }

        return response()->json(['message' => 'Sản phẩm đã thêm vào giỏ hàng']);
    }

    public function destroy($id)
    {
        if (Auth::check()) {
            CartItem::where('id', $id)->where('user_id', Auth::id())->delete();
        } else {
            $cart = session()->get('cart', []);
            unset($cart[$id]);
            session()->put('cart', $cart);
        }

        return response()->json(['message' => 'Sản phẩm đã được xóa']);
    }
}
