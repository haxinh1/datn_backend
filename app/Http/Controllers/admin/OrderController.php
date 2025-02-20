<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Requests\UpdateOrderRequest;
use App\Models\CartItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;



class OrderController extends Controller
{
    public function store(Request $request)
    {
        if (Auth::check()) {
            $cartItems = CartItem::where('user_id', Auth::id())->get();
        } else {
            $cartItems = session()->get('cart', []);
        }

        if (empty($cartItems)) {
            return response()->json(['message' => 'Giỏ hàng trống'], 400);
        }

        $totalAmount = array_sum(array_map(fn($item) => $item['quantity'] * 100, $cartItems));

        $order = Order::create([
            'code' => 'ORD' . time(),
            'user_id' => Auth::id(),
            'fullname' => $request->fullname,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'total_amount' => $totalAmount,
            'is_paid' => false,
        ]);

        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        } else {
            session()->forget('cart');
        }

        return response()->json(['message' => 'Đặt hàng thành công', 'order' => $order]);
    }
}
