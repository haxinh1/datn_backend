<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    /**
     * Lấy danh sách phương thức thanh toán
     */
    public function index()
    {
        return response()->json(Payment::all(), 200);
    }

    /**
     * Thêm mới phương thức thanh toán
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:payments,id',
            'name' => 'required|string|max:255',
            'logo' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], 400);
        }

        $payment = Payment::create([
            'parent_id' => $request->parent_id,
            'name' => $request->name,
            'logo' => $request->logo,
            'is_active' => $request->is_active ?? true
        ]);

        return response()->json([
            'message' => 'Phương thức thanh toán đã được tạo',
            'data' => $payment
        ], 201);
    }

    /**
     * Lấy thông tin chi tiết một phương thức thanh toán
     */
    public function show($id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Không tìm thấy phương thức thanh toán'], 404);
        }

        return response()->json($payment, 200);
    }

    /**
     * Cập nhật phương thức thanh toán
     */
    public function update(Request $request, $id)
    {
        $payment = Payment::find($id);

        if (!$payment) {
            return response()->json(['message' => 'Không tìm thấy phương thức thanh toán'], 404);
        }

        $validator = Validator::make($request->all(), [
            'parent_id' => 'nullable|exists:payments,id', // Cho phép cập nhật parent_id
            'name' => 'sometimes|string|max:255',
            'logo' => 'sometimes|string|max:255',
            'is_active' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Lỗi dữ liệu đầu vào',
                'errors' => $validator->errors()
            ], 400);
        }

        $payment->update($request->only(['parent_id', 'name', 'logo', 'is_active']));

        return response()->json([
            'message' => 'Cập nhật phương thức thanh toán thành công',
            'data' => $payment
        ], 200);
    }
}
