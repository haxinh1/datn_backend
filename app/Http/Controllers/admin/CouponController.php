<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CouponController extends Controller
{

    public function search(Request $request)
    {

        $query = Coupon::query();

        if ($request->filled('code')) {
            $code = trim($request->code);
            if ($code !== '') {
                $query->where('code', 'LIKE', "%{$code}%");
            }
        }
        if ($request->filled('title')) {
            $title = trim($request->title);
            if ($title !== '') {
                $query->where('title', 'LIKE', "%{$title}%");
            }
        }

        if ($request->filled('discount_type')) {
            $query->where('discount_type', $request->discount_type);
        }

        if ($request->filled('is_active')) {
            $query->where('is_active', $request->is_active);
        }

        $coupons = $query->orderByDesc('id')->paginate(10);

        return response()->json([
            'success' => true,
            'message' => 'Danh sách phiếu giảm giá đã lọc',
            'data' => $coupons,
        ]);
    }




    public function index()
    {
        $coupons = Coupon::withTrashed()->orderByDesc('id')->get();
        return response()->json([
            'success' => true,
            'message' => "Đây là danh sách mã giảm giá",
            'data' => $coupons,
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->only(['code', 'title', 'description', 'discount_type', 'discount_value', 'usage_limit', 'start_date', 'end_date', 'is_active']);

        $validator = Validator::make($data, [
            'code' => 'required|string|max:50|unique:coupons',
            'title' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percent,fix_amount',
            'discount_value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $data['is_active'] ?? 0;

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data['usage_count'] = 0;
        $data['is_expired'] = $data['end_date'] ? (now()->lessThanOrEqualTo($data['end_date']) ? 1 : 0) : 1;

        try {
            $coupon = Coupon::create($data);
            return response()->json([
                'success' => true,
                'message' => 'Thêm mã giảm giá thành công',
                'data' => $coupon,
            ], 201);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function show(string $id)
    {
        try {
            $coupon = Coupon::withTrashed()->findOrFail($id);
            return response()->json([
                'success' => true,
                'message' => 'Chi tiết mã giảm giá',
                'data' => $coupon,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Không có dữ liệu phù hợp',
            ], 404);
        }
    }


    public function update(Request $request, string $id)
    {
        $coupon = Coupon::withTrashed()->find($id);
        if (!$coupon) {
            return response()->json([
                'success' => false,
                'message' => 'Không tìm thấy dữ liệu',
            ], 404);
        }

        $data = $request->only(['code', 'title', 'description', 'discount_type', 'discount_value', 'usage_limit', 'start_date', 'end_date', 'is_active']);

        $validator = Validator::make($data, [
            'code' => 'required|string|max:50|unique:coupons,code,' . $id,
            'title' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:255',
            'discount_type' => 'required|in:percent,fix_amount',
            'discount_value' => 'required|numeric|min:0',
            'usage_limit' => 'nullable|integer|min:0',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'is_active' => 'boolean',
        ]);

        $data['is_active'] = $data['is_active'] ?? 0;

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ], 422);
        }

        $data['is_expired'] = $data['end_date'] ? (now()->lessThanOrEqualTo($data['end_date']) ? 1 : 0) : 1;

        try {
            $coupon->update($data);
            return response()->json([
                'success' => true,
                'message' => 'Cập nhật mã giảm giá thành công',
                'data' => $coupon,
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }

}
