<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderCancel;
use App\Models\OrderOrderStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

class OrderCancelController extends Controller
{
    /**
     * Get danh sách tất cả đơn hủy
     */
    public function index()
    {
        $orderCancels = OrderCancel::with([
            'order:user_id,id,code,total_amount,payment_id'
        ])->latest()->get();

        return response()->json([
            'order_cancels' => $orderCancels
        ]);
    }

    /**
     * Lấy danh sách đơn hủy theo user_id
     */
    public function showByUser($userId)
    {
        $orderCancels = OrderCancel::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })->with([
            'order:user_id,id,code,total_amount,payment_id'
        ])->latest()->get();

        return response()->json([
            'order_cancels' => $orderCancels
        ]);
    }


    /**
     * Client chủ động gửi yêu cầu hủy đơn
     */
    public function clientRequestCancel(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'user_id' => 'required|exists:users,id',
            'bank_account_number' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_qr' => 'nullable|string',
            'reason' => 'nullable|string',
        ]);
        // Lấy đơn hàng
        $order = Order::findOrFail($validated['order_id']);

        // Kiểm tra trạng thái đơn hàng: chỉ cho phép trạng thái 1,2,3
        if (!in_array($order->status_id, [1, 2, 3])) {
            return response()->json([
                'message' => 'Chỉ có thể hủy đơn khi đơn ở trạng thái Chờ thanh toán, Đã thanh toán trực tuyến hoặc Đang xử lý.'
            ], 400);
        }

        // Tạo đơn hủy
        $orderCancel = OrderCancel::create([
            'order_id' => $validated['order_id'],
            'bank_account_number' => $validated['bank_account_number'],
            'bank_name' => $validated['bank_name'],
            'bank_qr' => $validated['bank_qr'] ?? null,
            'reason' => $validated['reason'] ?? null,
            'status_id' => 8, // Hủy đơn
            'refund_proof' => '', // Chưa upload minh chứng
        ]);

        // Cập nhật trạng thái đơn gốc
        $order->update(['status_id' => 8]);

        // Ghi lịch sử
        OrderOrderStatus::create([
            'order_id' => $order->id,
            'order_status_id' => 8,
            'modified_by' => $validated['user_id'],
            'note' => 'Khách hàng yêu cầu hủy đơn hàng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);


        return response()->json([
            'message' => 'Yêu cầu hủy đơn đã được gửi thành công',
            'order_cancel' => $orderCancel
        ]);
    }


    /**
     * Admin chủ động hủy đơn và yêu cầu client gửi bank info
     */
    public function adminCancelOrder(Request $request, $orderId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'reason' => 'required'
        ]);

        $order = Order::findOrFail($orderId);

        // Kiểm tra trạng thái đơn hàng: cho phép hủy ở trạng thái 1,2,3,4,6
        if (!in_array($order->status_id, [1, 2, 3, 4, 6])) {
            return response()->json([
                'message' => 'Chỉ có thể hủy đơn khi đơn ở trạng thái Chờ xác nhận, Đang xử lý, Đã xác nhận, Đang giao hàng hoặc Chờ lấy hàng.'
            ], 400);
        }

     


        Mail::to($order->email)->send(new \App\Mail\OrderCancel($order));


        // Tạo đơn hủy
        $orderCancel = OrderCancel::create([
            'order_id' => $order->id,
            'bank_account_number' => '',
            'bank_name' => '',
            'bank_qr' => null,
            'reason' => $request->reason,
            'status_id' => 8,
            'refund_proof' => '',
        ]);

        // Cập nhật trạng thái đơn hàng
        $order->update(['status_id' => 8]);

        // Ghi lịch sử đơn hàng
        OrderOrderStatus::create([
            'order_id' => $order->id,
            'order_status_id' => 8,
            'modified_by' => $validated['user_id'],
            'note' => 'Admin hủy đơn hàng',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json([
            'message' => 'Đơn hàng đã được hủy thành công',
            'order_cancel' => $orderCancel
        ]);
    }


    /**
     * Client submit thông tin ngân hàng sau khi admin yêu cầu
     */
    public function clientSubmitBankInfo(Request $request, $cancelId)
    {
        $validated = $request->validate([
            'bank_account_number' => 'required|string|max:255',
            'bank_name' => 'required|string|max:255',
            'bank_qr' => 'nullable|string',
        ]);

        $orderCancel = OrderCancel::findOrFail($cancelId);

        $orderCancel->update([
            'bank_account_number' => $validated['bank_account_number'],
            'bank_name' => $validated['bank_name'],
            'bank_qr' => $validated['bank_qr'] ?? null,
        ]);

        return response()->json(['message' => 'Đã gửi thông tin ngân hàng thành công']);
    }

    /**
     * Admin xác nhận hoàn tiền, upload refund_proof
     */
    public function adminRefundOrder(Request $request, $cancelId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'refund_proof' => 'required|string|max:255',
        ]);

        $orderCancel = OrderCancel::findOrFail($cancelId);

        // Cập nhật đơn hủy
        $orderCancel->update([
            'refund_proof' => $validated['refund_proof'],
            'status_id' => 12, // Hoàn tiền thành công
        ]);

        // Cập nhật đơn gốc
        $order = $orderCancel->order;
        if ($order) {
            $order->update(['status_id' => 12]);

            // Ghi lịch sử đơn hàng
            \App\Models\OrderOrderStatus::create([
                'order_id' => $order->id,
                'order_status_id' => 12,
                'employee_evidence' => $validated['refund_proof'],
                'modified_by' => $validated['user_id'],
                'note' => 'Admin xác nhận hoàn tiền đơn hàng',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Đã hoàn tiền và cập nhật đơn hàng thành công',
            'order_cancel' => $orderCancel
        ]);
    }
}
