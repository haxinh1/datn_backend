<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\CartItem;
use App\Models\OrderOrderStatus;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Đặt hàng (Thanh toán COD hoặc chuyển khoản)
     */
    public function store(Request $request)
{
    DB::beginTransaction(); // Bắt đầu transaction

    try {
        // Lấy giỏ hàng
        if (Auth::check()) {
            $cartItems = CartItem::where('user_id', Auth::id())->with('product')->get();
        } else {
            $cartItems = session()->get('cart', []);
        }

        if (empty($cartItems)) {
            return response()->json(['message' => 'Giỏ hàng trống'], 400);
        }

        // Tính tổng tiền
        $totalAmount = collect($cartItems)->sum(function ($item) {
            $product = Product::find($item['product_id']);
            return $item['quantity'] * ($product ? $product->sale_price : 0);
        });

        if ($totalAmount <= 0) {
            return response()->json(['message' => 'Giá trị đơn hàng không hợp lệ'], 400);
        }

        // Tạo đơn hàng
        $order = Order::create([
            'code' => 'ORD' . time(),
            'user_id' => Auth::id(),
            'fullname' => $request->fullname,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'total_amount' => $totalAmount,
            'status_id' => 1, // Trạng thái mặc định: Đang xử lý
            'payment_id' => null,
        ]);

        // Thêm trạng thái đầu tiên vào bảng `order_order_statuses`
        OrderOrderStatus::create([
            'order_id' => $order->id,
            'order_status_id' => 1, // Trạng thái "Đang xử lý"
            'modified_by' => Auth::id(), // Nếu là khách vãng lai thì để NULL
            'note' => 'Đơn hàng mới được tạo.',
            'employee_evidence' => null,
        ]);

        // Thêm sản phẩm vào chi tiết đơn hàng
        foreach ($cartItems as $item) {
            $product = Product::find($item['product_id']);

            if ($product) {
                $order->orderItems()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'sell_price' => $product->sale_price,
                ]);
            }
        }

        // Xóa giỏ hàng sau khi đặt hàng
        if (Auth::check()) {
            CartItem::where('user_id', Auth::id())->delete();
        } else {
            session()->forget('cart');
            session()->save(); // Đảm bảo session được cập nhật
        }

        DB::commit(); // Commit transaction

        return response()->json([
            'message' => 'Đặt hàng thành công',
            'order' => $order,
        ], 201);

    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
    }
}



    /**
     * Lấy chi tiết đơn hàng
     */
    public function show($id)
    {
        $order = Order::with(['orderItems.product', 'payment', 'status', 'orderStatuses'])
            ->find($id);

        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        return response()->json(['order' => $order], 200);
    }
}
