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
    public function index()
    {
        $orders = Order::with(['orderItems.product', 'payment', 'user', 'orderStatuses'])->orderBy('created_at', 'desc')->get();
        return response()->json($orders);
    }

    public function store(Request $request)
    {
        if (Auth::check()) {
            $cartItems = CartItem::where('user_id', Auth::id())->with('product')->get();
        } else {
            $cartItems = collect(session()->get('cart', [])); // Chuyển thành Collection
        }
        
        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Giỏ hàng trống'], 400);
        }
        

        if (empty($cartItems) || (is_array($cartItems) && count($cartItems) === 0)) {
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

        // Lưu sản phẩm vào `order_items`
        foreach ($cartItems as $item) {
            $order->orderItems()->create([
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->product->sell_price, // Giá tại thời điểm đặt hàng
            ]);
        }

        // Xóa giỏ hàng sau khi đặt hàng
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        } else {
            session()->forget('cart');
        }

        return response()->json(['message' => 'Đặt hàng thành công', 'order' => $order]);
    }
    public function show($id)
    {
        $order = Order::with(['orderItems.product', 'payment', 'user'])->findOrFail($id);
        return response()->json($order);
    }
    public function updateStatus(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $request->validate([
            'status_id' => 'required|exists:order_statuses,id',
        ]);

        // Cập nhật trạng thái mới vào `order_order_statuses`
        $order->orderStatuses()->attach($request->status_id, [
            'modified_by' => Auth::id(),
            'note' => $request->note ?? '',
        ]);

        return response()->json(['message' => 'Cập nhật trạng thái đơn hàng thành công']);
    }
}
