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
        // ✅ Kiểm tra user có đăng nhập không
        $userId = Auth::id();
        if (!$userId) {
            return response()->json(['message' => 'Người dùng chưa xác thực'], 401);
        }

        // ✅ Kiểm tra dữ liệu đầu vào
        $request->validate([
            'order_status_id' => 'required|exists:order_statuses,id',
            'note' => 'nullable|string|max:255',
            'employee_evidence' => 'nullable|string', // Nếu đã đổi DB từ JSON sang string
        ]);

        // ✅ Kiểm tra đơn hàng có tồn tại không
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }

        DB::beginTransaction();
        try {
            // ✅ Lưu trạng thái mới vào bảng `order_order_statuses`
            $orderOrderStatus = OrderOrderStatus::create([
                'order_id' => $orderId,
                'order_status_id' => $request->order_status_id,
                'modified_by' => $userId, // ✅ Đảm bảo modified_by được lưu
                'note' => $request->note,
                'employee_evidence' => $request->employee_evidence ?? '', // Tránh null nếu dùng kiểu string
            ]);

            // ✅ Cập nhật trạng thái đơn hàng trong bảng `orders`
            $order->update(['status_id' => $request->order_status_id]);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật trạng thái đơn hàng thành công',
                'order' => $order,
                'history' => $orderOrderStatus
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Lỗi khi cập nhật trạng thái đơn hàng',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
