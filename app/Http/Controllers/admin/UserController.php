<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;
use App\Models\Order;
use App\Events\UserStatusUpdated;

class UserController extends Controller
{
    public function index()
    {
        $user = User::with(['address' => function ($query) {
            $query->select('user_id', 'address', 'detail_address');
        }])->where('role', 'admin')->orWhere('role', 'manager')->get();
        return response()->json($user, 200);
    }
    
    public function index1()
    {
        $user = User::with(['address' => function ($query) {
            $query->select('user_id', 'address', 'detail_address');
        }])->where('role', 'customer')->get();
        return response()->json($user, 200);
    }

    public function show($id)
    {
        $user = User::with(['address' => function ($query) {
            $query->select('user_id', 'address', 'detail_address');
        }])->find($id);
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng'], 404);
        }
        return response()->json($user, 200);
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'phone_number'   => 'required|unique:users,phone_number|max:20',
            'email'          => 'nullable|email|unique:users,email|max:100',
            'password'       => 'required|min:6',
            'fullname'       => 'required|max:100',
            'avatar'         => 'nullable|url',
            'gender'         => ['nullable', Rule::in(['male', 'female', 'other'])],
            'birthday'       => 'nullable|date',
            'loyalty_points' => 'nullable|integer|min:0',
            'role'           => ['nullable', Rule::in(['customer', 'admin', 'manager'])],
            'status'         => ['nullable', Rule::in(['active', 'inactive', 'banned'])],
            'google_id'      => 'nullable|numeric',
        ]);

        $user = new User();
        $user->phone_number   = $validatedData['phone_number'];
        $user->email          = $validatedData['email'] ?? null;
        $user->password       = Hash::make($validatedData['password']);
        $user->fullname       = $validatedData['fullname'] ?? null;
        $user->avatar         = $validatedData['avatar'] ?? null;
        $user->gender         = $validatedData['gender'] ?? null;
        $user->birthday       = $validatedData['birthday'] ?? null;
        $user->loyalty_points = $validatedData['loyalty_points'] ?? 0;
        $user->role           = $validatedData['role'] ?? 'customer';
        $user->status         = $validatedData['status'] ?? 'active';
        $user->google_id      = $validatedData['google_id'] ?? null;
        $user->total_spent    = 0;
        $user->rank_points    = 0;
        $user->rank           = 'Thành Viên';
        $user->save();

        return response()->json($user, 201);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Không tìm thấy người dùng'], 404);
        }

        $validatedData = $request->validate([
            'phone_number' => 'sometimes|regex:/^0[0-9]{9}$/|required|max:20|unique:users,phone_number,' . $user->id,
            'email' => 'sometimes|required|email|max:100|unique:users,email,' . $user->id,
            'fullname' => 'sometimes|required|max:100',
            'avatar' => 'sometimes|nullable|url',
            'gender' => ['sometimes', 'nullable', Rule::in(['male', 'female', 'other'])],
            'birthday' => 'sometimes|nullable|date',
            'role' => ['sometimes', Rule::in(['customer', 'admin', 'manager'])],
            'status' => ['sometimes', Rule::in(['active', 'inactive', 'banned'])], // Thêm banned
        ]);

        if (isset($validatedData['phone_number'])) {
            $user->phone_number = $validatedData['phone_number'];
        }
        if (array_key_exists('email', $validatedData)) {
            $user->email = $validatedData['email'];
        }
        if (isset($validatedData['fullname'])) {
            $user->fullname = $validatedData['fullname'];
        }
        if (isset($validatedData['avatar'])) {
            $user->avatar = $validatedData['avatar'];
        }
        if (isset($validatedData['gender'])) {
            $user->gender = $validatedData['gender'];
        }
        if (isset($validatedData['birthday'])) {
            $user->birthday = $validatedData['birthday'];
        }
        if (isset($validatedData['role'])) {
            $user->role = $validatedData['role'];
        }
        if (isset($validatedData['status'])) {
            $user->status = $validatedData['status'];
        }

        $user->save();

        if (isset($validatedData['status'])) {
            event(new UserStatusUpdated($user));
        }

        return response()->json($user, 200);
    }

    public function register(Request $request) {}

    public function login(Request $request)
    {


        $validatedData = $request->validate([
            'phone_number' => 'required',
            'password'     => 'required'
        ]);

        $user = User::where(function ($query) use ($validatedData) {
            $query->where('phone_number', $validatedData['phone_number'])
                ->orWhere('email', $validatedData['phone_number']);
        })
            ->where(function ($query) {
                $query->where('role', 'admin')
                    ->orWhere('role', 'manager');
            })
            ->first();



        if (!$user || !Hash::check($validatedData['password'], $user->password)) {
            return response()->json([
                'message' => 'Thông tin đăng nhập không đúng hoặc tài khoản không phải admin'
            ], 401);
        }

        if ($user->status === 'inactive') {
            return response()->json([
                'message' => 'Tài khoản của bạn đã dừng hoạt động'
            ], 403);
        }



        $token = $user->createToken('admin_token')->plainTextToken;

        return response()->json([
            'message'      => 'Đăng nhập admin thành công',
            'admin'        => $user,
            'access_token' => $token,
            'token_type'   => 'Bearer'
        ], 200);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Đăng xuất thành công'
        ], 200);
    }


    public function changePassword(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại'], 404);
        }
        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|same:new_password'
        ]);

        // $user = $request->user();


        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu hiện tại không chính xác.'], 400);
        }
        if (Hash::check($request->new_password, $user->password)) {
            return response()->json(['message' => 'Mật khẩu mới không được trùng với mật khẩu cũ.'], 400);
        }


        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Mật khẩu đã được thay đổi thành công.',
            'user_id' => $user->id
        ], 200);
    }


    //  lịch sử điểm theo người dùng
     
    public function pointsHistory($id)
    {
        $user = User::find($id);
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại'], 404);
        }

        $pointHistory = $user->pointTransactions()->with('order:id,code')->orderBy('created_at', 'desc')->get();

        return response()->json($pointHistory, 200);
    }
}



