<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\OrderStatus;
use Illuminate\Http\Request;

class OrderStatusController extends Controller
{
    /**
     * Lấy danh sách trạng thái đơn hàng.
     */
    public function index()
    {
        $statuses = OrderStatus::orderBy('ordinal')->get();
        return response()->json(['status' => 'success', 'data' => $statuses], 200);
    }

    /**
     * Tạo trạng thái đơn hàng mới.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:order_statuses,name',
            'ordinal' => 'nullable|integer',
        ]);

        try {
            $status = OrderStatus::create($validated);
            return response()->json(['message' => 'Trạng thái đơn hàng đã được tạo', 'data' => $status], 201);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Lấy thông tin trạng thái đơn hàng cụ thể.
     */
    public function show($id)
    {
        $status = OrderStatus::find($id);

        if (!$status) {
            return response()->json(['message' => 'Trạng thái đơn hàng không tồn tại'], 404);
        }

        return response()->json(['status' => 'success', 'data' => $status], 200);
    }

    /**
     * Cập nhật trạng thái đơn hàng.
     */
    public function update(Request $request, $id)
    {
        $status = OrderStatus::find($id);

        if (!$status) {
            return response()->json(['message' => 'Trạng thái đơn hàng không tồn tại'], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:50|unique:order_statuses,name,' . $id,
            'ordinal' => 'nullable|integer',
        ]);

        try {
            $status->update($validated);
            return response()->json(['message' => 'Cập nhật trạng thái thành công', 'data' => $status], 200);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Lỗi hệ thống', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Xóa trạng thái đơn hàng (chỉ khi không có đơn hàng nào sử dụng).
     */
    public function destroy($id)
    {
        $status = OrderStatus::find($id);

        if (!$status) {
            return response()->json(['message' => 'Trạng thái đơn hàng không tồn tại'], 404);
        }

        // Kiểm tra xem trạng thái có đang được sử dụng không
        if ($status->orderHistories()->exists()) {
            return response()->json(['message' => 'Không thể xóa trạng thái này vì đang được sử dụng trong đơn hàng'], 400);
        }

        $status->delete();
        return response()->json(['message' => 'Trạng thái đơn hàng đã được xóa thành công'], 200);
    }
}
