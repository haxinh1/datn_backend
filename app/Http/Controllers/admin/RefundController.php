<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RefundDetail;
use Illuminate\Support\Facades\DB;

class RefundController extends Controller
{
    // Khách hàng gửi yêu cầu hoàn tiền
    public function requestRefund(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_return_id' => 'required|exists:order_returns,id',
            'note' => 'nullable|string',
            'employee_evidence' => 'nullable|string'
        ]);

        $refund = RefundDetail::create([
            'order_id' => $request->order_id,
            'order_return_id' => $request->order_return_id,
            'note' => $request->note,
            'employee_evidence' => $request->employee_evidence,
            'status' => 12
        ]);

        // Cập nhật trạng thái đơn hàng thành "Yêu cầu hoàn tiền" (status_id = 12)
        DB::table('orders')->where('id', $request->order_id)->update(['status_id' => 12]);

        // Cập nhật trạng thái yêu cầu trả hàng thành "Yêu cầu hoàn tiền" (status_id = 12)
        DB::table('order_returns')->where('id', $request->order_return_id)->update(['status_id' => 12]);

        $userId = $request->input('user_id');
        // Lưu lịch sử trạng thái trong bảng order_order_statuses
        DB::table('order_order_statuses')->insert([
            'order_id' => $request->order_id,
            'order_status_id' => 12,  // Trạng thái "Yêu cầu hoàn tiền"
            'modified_by' => $userId,
            'note' => 'Yêu cầu hoàn tiền',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Yêu cầu hoàn tiền đã được gửi!', 'refund' => $refund]);
    }

    // Admin xác nhận đã hoàn tiền
    public function confirmRefund(Request $request, $id)
    {
        $request->validate([
            'note' => 'nullable|string',
            'employee_evidence' => 'nullable|string',
            'user_id' => 'nullable|integer'
        ]);
    
        $refund = RefundDetail::findOrFail($id);

        // Cập nhật trạng thái hoàn tiền thành công (status = 13)
        $refund->update([
            'status' => 13,
            'note' => $request->note,
            'employee_evidence' => $request->employee_evidence
        ]);

        // Cập nhật trạng thái đơn hàng thành "Hoàn tiền thành công" (status_id = 13)
        DB::table('orders')->where('id', $refund->order_id)->update(['status_id' => 13]);

        // Cập nhật trạng thái trả hàng thành "Hoàn tiền thành công"
        DB::table('order_returns')->where('id', $refund->order_return_id)->update(['status_id' => 13]);

        $userId = $request->input('user_id');

        // Lưu lịch sử trạng thái trong bảng order_order_statuses
        DB::table('order_order_statuses')->insert([
            'order_id' => $refund->order_id,
            'order_status_id' => 13,  // Trạng thái "Hoàn tiền thành công"
            'modified_by' => $userId,
            'note' => 'Hoàn tiền thành công',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Đã xác nhận hoàn tiền thành công', 'refund' => $refund]);
    }

    // Lấy danh sách tất cả bản ghi hoàn tiền
    public function index()
    {
        $refunds = RefundDetail::with(['order', 'orderReturn'])->get();
        return response()->json(['refunds' => $refunds]);
    }

    // Xem chi tiết 1 bản ghi hoàn tiền
    public function show($id)
    {
        $refund = RefundDetail::with(['order', 'orderReturn'])->findOrFail($id);
        return response()->json(['refund' => $refund]);
    }
}
