<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\OrderOrderStatus;
use App\Models\Order;
use App\Models\OrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Events\OrderStatusUpdated;
use App\Models\OrderReturn;
use App\Models\Product;

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
    public function indexMultiple(Request $request)
    {
        $orderIds = $request->input('order_ids');

        if (empty($orderIds)) {
            return response()->json(['message' => 'Không có đơn hàng nào để lấy lịch sử'], 400);
        }

        // Lấy lịch sử trạng thái của nhiều đơn hàng
        $orderStatuses = OrderOrderStatus::whereIn('order_id', $orderIds)
            ->with(['status', 'modifiedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        // Phân nhóm kết quả theo từng order_id
        $groupedStatuses = $orderStatuses->groupBy('order_id');

        return response()->json([
            'status' => 'success',
            'data' => $groupedStatuses,
        ]);
    }
    public function getOrdersByModifiedBy(Request $request, $modified_by)
    {
        // Kiểm tra xem user_id có tồn tại trong bảng users không
        $userExists = \App\Models\User::where('id', $modified_by)->exists();
        if (!$userExists) {
            return response()->json(['message' => 'Người dùng không tồn tại'], 404);
        }


        try {
            $orderStatuses = OrderOrderStatus::where('modified_by', $modified_by)
                ->with('order:id,code')  
                ->orderBy('created_at', 'desc') 
                ->get(); 

            // Trả về danh sách các order_statuses chỉ có mã đơn hàng
            return response()->json([
                'status' => 'success',
                'data' => $orderStatuses
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Lỗi khi truy vấn dữ liệu',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Cập nhật trạng thái đơn hàng.
     */
    public function updateStatus(Request $request, $orderId)
    {
        // Kiểm tra dữ liệu đầu vào
        $request->validate([
            'order_status_id' => 'required|exists:order_statuses,id',
            'note' => 'nullable|string|max:255',
            'employee_evidence' => 'nullable|string',
            'user_id' => 'nullable|integer|exists:users,id',
        ]);

        // Kiểm tra đơn hàng có tồn tại không
        $order = Order::find($orderId);
        if (!$order) {
            return response()->json(['message' => 'Không tìm thấy đơn hàng'], 404);
        }
        // Kiểm tra trạng thái chuyển đổi hợp lệ
        $validStatusTransitions = [
            2 => [3, 8], // Đã thanh toán -> Đang xử lý hoặc Hủy đơn
            3 => [4, 8], // Đang xử lý -> Đang giao hàng hoặc Hủy đơn
            4 => [5, 6], // Đang giao hàng -> Đã giao hàng hoặc Giao hàng thất bại
            5 => [7, 9],    // Đã giao hàng -> Hoàn thành hoặc chờ xử lí trả hàng
            6 => [4, 8],    // Giao hàng thất bại -> Giao hàng lại hoặc hủy đơn
            7 => [9], // Đã hoàn thành -> Chờ xử lí trả hàng
            9 => [10, 11], // Chờ xử lý trả hàng -> Chấp nhận hoặc Từ chối
            12 => [13],   // Hoàn tiền thành công -> Hàng đang quay về shop
            13 => [14],   // Hàng đang quay về shop -> Người bán đã nhận hàng


        ];
        // Kiểm tra trạng thái hiện tại có thể chuyển sang trạng thái mới không
        if (
            !isset($validStatusTransitions[$order->status_id]) ||
            !in_array($request->order_status_id, $validStatusTransitions[$order->status_id])
        ) {
            return response()->json([
                'message' => 'Không thể chuyển trạng thái từ ' . $order->status_id . ' sang ' . $request->order_status_id
            ], 400);
        }
       
        DB::beginTransaction();
        try {
            $userId = $request->input('user_id', null);
            $orderOrderStatus = OrderOrderStatus::create([
                'order_id' => $orderId,
                'order_status_id' => $request->order_status_id,
                'modified_by' =>  $userId,
                'employee_evidence' => $request->employee_evidence ?? '', 
            ]);

            // Cập nhật trạng thái đơn hàng trong bảng `orders`
            $order->update(['status_id' => $request->order_status_id]);

            // Phát sự kiện để cập nhật trạng thái thời gian thực
            event(new OrderStatusUpdated($order)); 

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
    public function batchUpdateByStatus(Request $request)
    {
        // Validate dữ liệu đầu vào
        $request->validate([
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'integer|exists:orders,id', 
            'current_status' => 'required|integer|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14',
            'new_status' => 'required|integer|in:1,2,3,4,5,6,7,8,9,10,11,12,13,14'
        ]);

        try {
            DB::beginTransaction();

            // Lấy danh sách đơn hàng có trạng thái cần cập nhật
            $orders = Order::whereIn('id', $request->order_ids)
                ->where('status_id', $request->current_status)
                ->get();

            // Nếu không có đơn hàng nào thỏa mãn, trả về lỗi
            if ($orders->isEmpty()) {
                return response()->json([
                    'message' => 'Không có đơn hàng nào thỏa mãn yêu cầu'
                ], 400);
            }

            // Kiểm tra xem trạng thái mới có hợp lệ không
            $validStatusTransitions = [
                2 => [3, 8], // Đã thanh toán -> Đang xử lý hoặc Hủy đơn
                3 => [4, 8], // Đang xử lý -> Đang giao hàng hoặc Hủy đơn
                4 => [5, 6], // Đang giao hàng -> Đã giao hàng hoặc Giao hàng thất bại
                5 => [7],    // Đã giao hàng -> Hoàn thành
                9 => [10, 11], // Chờ xử lý trả hàng -> Chấp nhận hoặc Từ chối
                13 => [14],   // Hàng đang quay về shop -> Người bán nhận được hàng

            ];

            // Kiểm tra trạng thái chuyển đổi hợp lệ
            if (
                !isset($validStatusTransitions[$request->current_status]) ||
                !in_array($request->new_status, $validStatusTransitions[$request->current_status])
            ) {
                return response()->json([
                    'message' => 'Không thể chuyển trạng thái từ ' . $request->current_status . ' sang ' . $request->new_status
                ], 400);
            }

            // Lưu lịch sử trạng thái cho từng đơn hàng trong mảng
            foreach ($orders as $order) {
                // Lưu trạng thái vào bảng `order_order_statuses` (lịch sử trạng thái)
                OrderOrderStatus::create([
                    'order_id' => $order->id,
                    'order_status_id' => $request->new_status,
                    'modified_by' => Auth::id(),
                    'note' => $request->note,
                ]);
                // Phát sự kiện để cập nhật trạng thái thời gian thực
                event(new OrderStatusUpdated($order));
            }

            // Cập nhật trạng thái mới cho tất cả các đơn hàng
            Order::whereIn('id', $request->order_ids)
                ->where('status_id', $request->current_status)
                ->update(['status_id' => $request->new_status]);

            DB::commit();

            return response()->json([
                'message' => 'Cập nhật trạng thái thành công!',
                'updated_orders' => $orders->pluck('id') 
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }
}
