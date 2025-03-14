<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\OrderItem;
use Illuminate\Http\Request;

class OrderItemController extends Controller
{
    /**
     * Lấy danh sách sản phẩm trong đơn hàng
     */
    public function index($orderId)
    {
        $items = OrderItem::where('order_id', $orderId)->with('product')->get();
        return response()->json($items);
    }
}
