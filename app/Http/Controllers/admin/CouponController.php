<?php

namespace App\Http\Controllers\admin;
use App\Http\Controllers\Controller;
use App\Models\Coupon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        // Lấy danh sách, nếu là private thì load users
        $coupons = $query->orderByDesc('id')->get()->map(function ($coupon) {
            if ($coupon->coupon_type === 'private') {
                $coupon->load('users');
            }
            return $coupon;
        });

        return response()->json([
            'success' => true,
            'message' => 'Danh sách phiếu giảm giá đã lọc',
            'data' => $coupons,
        ]);
    }


    // Hàm cho client lấy ra danh sách voucher có thể dùng + còn is_active và private




    public function index()
    {
        $coupons = Coupon::orderByDesc('id')->get()->map(function ($coupon) {
            if ($coupon->coupon_type === 'private') {
                $coupon->load('users');
            }
            return $coupon;
        });

        return response()->json([
            'success' => true,
            'message' => "Đây là danh sách mã giảm giá",
            'data' => $coupons,
        ]);
    }



    public function store(Request $request)
    {
        $data = $request->only([
            'code', 'title', 'description', 'discount_type', 'discount_value',
            'usage_limit', 'start_date', 'end_date', 'is_active', 'coupon_type', 'rank', 'user_ids'
        ]);

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
            'coupon_type' => 'required|in:public,private,rank',
            'rank' => 'nullable|in:bronze,silver,gold,diamond',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Kiểm tra logic: private cần user_ids, rank cần rank
        if ($data['coupon_type'] === 'private' && empty($data['user_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Private coupon cần có danh sách user_ids',
            ], 422);
        }
        if ($data['coupon_type'] === 'rank' && empty($data['rank'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rank coupon cần có rank',
            ], 422);
        }

        try {
            $coupon = Coupon::create($data);

            // Nếu là private coupon, liên kết với users
            if ($data['coupon_type'] === 'private') {
                $coupon->users()->sync($data['user_ids']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Thêm mã giảm giá thành công',
                'data' => $coupon->load('users'),
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
            $coupon = Coupon::withTrashed()->with('users')->findOrFail($id);
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

        $data = $request->only([
            'code', 'title', 'description', 'discount_type', 'discount_value',
            'usage_limit', 'start_date', 'end_date', 'is_active', 'coupon_type', 'rank', 'user_ids'
        ]);

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
            'coupon_type' => 'required|in:public,private,rank',
            'rank' => 'nullable|in:bronze,silver,gold,diamond',
            'user_ids' => 'nullable|array',
            'user_ids.*' => 'exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Lỗi nhập liệu',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Kiểm tra logic
        if ($data['coupon_type'] === 'private' && empty($data['user_ids'])) {
            return response()->json([
                'success' => false,
                'message' => 'Private coupon cần có user_ids',
            ], 422);
        }
        if ($data['coupon_type'] === 'rank' && empty($data['rank'])) {
            return response()->json([
                'success' => false,
                'message' => 'Rank coupon cần có rank',
            ], 422);
        }

        try {
            $coupon->update($data);

            // Cập nhật danh sách user
            if ($data['coupon_type'] === 'private') {
                $coupon->users()->sync($data['user_ids']);
            } else {
                $coupon->users()->detach(); // Nếu đổi về public/rank, xóa users
            }

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật mã giảm giá thành công',
                'data' => $coupon->load('users'),
            ], 200);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => $th->getMessage(),
            ], 500);
        }
    }


    public function availableCoupons(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $now = now();

        $query = Coupon::where('is_active', true)
            ->where(function ($query) use ($now) {
                $query->whereNull('start_date')->orWhere('start_date', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('end_date')->orWhere('end_date', '>=', $now);
            })
            ->where(function ($query) {
                $query->whereNull('usage_limit')->orWhereColumn('usage_count', '<', 'usage_limit');
            });

        // Nếu user đăng nhập, lọc theo cả 3 loại coupon_type
        if ($user) {
            $query->where(function ($query) use ($user) {
                $query->where('coupon_type', 'public')
                    ->orWhere(function ($q) use ($user) {
                        $q->where('coupon_type', 'private')
                            ->whereHas('users', function ($uq) use ($user) {
                                $uq->where('users.id', $user->id);
                            });
                    })
                    ->orWhere(function ($q) use ($user) {
                        $q->where('coupon_type', 'rank')
                            ->where('rank', $user->rank ?? '');
                    });
            });
        } else {
            // Nếu chưa đăng nhập, chỉ lấy coupon công khai
            $query->where('coupon_type', 'public');
        }

        $coupons = $query->orderByDesc('id')->with('users')->get();

        return response()->json([
            'success' => true,
            'message' => 'Danh sách mã giảm giá có thể sử dụng',
            'data' => $coupons,
        ]);
    }



}
