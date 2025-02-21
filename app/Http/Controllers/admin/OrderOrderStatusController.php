<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;

use App\Models\OrderOrderStatus;
use App\Http\Requests\StoreOrderOrderStatusRequest;
use App\Http\Requests\UpdateOrderOrderStatusRequest;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Support\Facades\Auth;

class OrderOrderStatusController extends Controller
{
    /**
     * Lấy danh sách trạng thái của đơn hàng
     */
    public function index($orderId)
    {
        $orderStatuses = OrderOrderStatus::where('order_id', $orderId)
            ->with(['status', 'modifiedBy'])
            ->get();

        return response()->json($orderStatuses);
    }

    /**
     * Cập nhật trạng thái đơn hàng
     */
    public function updateStatus(Request $request, $orderId)
    {
        $order = Order::findOrFail($orderId);
        
        // Kiểm tra trạng thái có tồn tại không
        $status = OrderStatus::findOrFail($request->status_id);

        $orderStatus = OrderOrderStatus::create([
            'order_id' => $order->id,
            'order_status_id' => $request->status_id,
            'modified_by' => Auth::id(),
            'note' => $request->note,
            'employee_evidence' => json_encode($request->employee_evidence),
            'customer_confirmation' => $request->customer_confirmation
        ]);

        return response()->json(['message' => 'Cập nhật trạng thái đơn hàng thành công', 'order_status' => $orderStatus]);
    }
}
