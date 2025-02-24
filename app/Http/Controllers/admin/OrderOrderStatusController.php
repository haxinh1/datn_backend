<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\OrderOrderStatus;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderOrderStatusController extends Controller
{
    /**
     * Lấy lịch sử trạng thái của một đơn hàng.
     */
    public function index($orderId)
    {
        $orderStatuses = OrderOrderStatus::where('order_id', $orderId)
            ->with(['status', 'modifiedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $orderStatuses,
        ]);
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     */
    public function updateStatus(Request $request, $orderId)
    {
        $request->validate([
            'order_status_id' => 'required|exists:order_statuses,id',
            'note' => 'nullable|string|max:255',
            'employee_evidence' => 'nullable|json',
        ]);

        // Kiểm tra đơn hàng có tồn tại không
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        // Kiểm tra trạng thái có tồn tại không
        $orderStatus = OrderStatus::find($request->order_status_id);
        if (!$orderStatus) {
            return response()->json(['message' => 'Trạng thái đơn hàng không hợp lệ'], 400);
        }

        // Lưu trạng thái mới vào bảng order_order_statuses
        $orderOrderStatus = OrderOrderStatus::create([
            'order_id' => $orderId,
            'order_status_id' => $request->order_status_id,
            'modified_by' => Auth::id(),
            'note' => $request->note,
            'employee_evidence' => $request->employee_evidence ? json_decode($request->employee_evidence, true) : null,
        ]);

        // Cập nhật trạng thái chính của đơn hàng
        $order->update(['status_id' => $request->order_status_id]);

        return response()->json([
            'message' => 'Cập nhật trạng thái đơn hàng thành công',
            'data' => $orderOrderStatus,
        ], 200);
    }
}
