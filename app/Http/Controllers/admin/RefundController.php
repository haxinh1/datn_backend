<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\OrderReturn;
use Illuminate\Http\Request;
use App\Models\RefundDetail;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    public function requestRefundByOrder(Request $request, $orderId)
    {
        $request->validate([
            'note' => 'nullable|string',
            'employee_evidence' => 'nullable|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $returns = OrderReturn::where('order_id', $orderId)->get();

        if ($returns->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy yêu cầu trả hàng cho đơn này'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($returns as $return) {
                RefundDetail::create([
                    'order_id' => $orderId,
                    'order_return_id' => $return->id,
                    'note' => $request->note,
                    'employee_evidence' => $request->employee_evidence,
                    'status' => 12
                ]);

                $return->update(['status_id' => 12]); // "Yêu cầu hoàn tiền"
            }

            Order::where('id', $orderId)->update(['status_id' => 12]);

            DB::table('order_order_statuses')->insert([
                'order_id' => $orderId,
                'order_status_id' => 12,
                'modified_by' => $request->user_id,
                'note' => 'Yêu cầu hoàn tiền',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Đã gửi yêu cầu hoàn tiền cho đơn hàng #' . $orderId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi khi gửi yêu cầu hoàn tiền', 'error' => $e->getMessage()], 500);
        }
    }

    // 2. Admin xác nhận hoàn tiền theo order_id
    public function confirmRefundByOrder(Request $request, $orderId)
    {
        $request->validate([
            'note' => 'nullable|string',
            'employee_evidence' => 'nullable|string',
            'user_id' => 'required|exists:users,id'
        ]);

        $refunds = RefundDetail::where('order_id', $orderId)->get();
        $returns = OrderReturn::where('order_id', $orderId)->get();

        if ($refunds->isEmpty()) {
            return response()->json(['message' => 'Không tìm thấy bản ghi hoàn tiền'], 404);
        }

        DB::beginTransaction();
        try {
            foreach ($refunds as $refund) {
                $refund->update([
                    'status' => 13,
                    'note' => $request->note,
                    'employee_evidence' => $request->employee_evidence
                ]);
            }

            foreach ($returns as $return) {
                $return->update(['status_id' => 13]); // "Hoàn tiền thành công"
            }

            Order::where('id', $orderId)->update(['status_id' => 13]);

            DB::table('order_order_statuses')->insert([
                'order_id' => $orderId,
                'order_status_id' => 13,
                'modified_by' => $request->user_id,
                'note' => 'Hoàn tiền thành công',
                'created_at' => now(),
                'updated_at' => now()
            ]);

            DB::commit();
            return response()->json(['message' => 'Đã xác nhận hoàn tiền thành công cho đơn hàng #' . $orderId]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Lỗi xác nhận hoàn tiền', 'error' => $e->getMessage()], 500);
        }
    }

    // 3. Lấy danh sách bản ghi hoàn tiền
    public function index()
    {
        $refunds = RefundDetail::with(['order', 'orderReturn'])->get();
        return response()->json(['refunds' => $refunds]);
    }

    // 4. Lấy chi tiết 1 bản ghi hoàn tiền
    public function show($id)
    {
        $refund = RefundDetail::with(['order', 'orderReturn'])->findOrFail($id);
        return response()->json(['refund' => $refund]);
    }
}
