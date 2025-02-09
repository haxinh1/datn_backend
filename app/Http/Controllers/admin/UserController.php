<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $users = User::paginate(10);
        return response()->json($users, 200);
    }

    public function show($id)
    {
        $user = User::find($id);
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
            'fullname'       => 'nullable|max:100',
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
            'phone_number'   => 'sometimes|required|max:20|unique:users,phone_number,'.$user->id,
            'email'          => 'sometimes|nullable|email|max:100|unique:users,email,'.$user->id,
            'password'       => 'sometimes|required|min:6',
            'fullname'       => 'sometimes|nullable|max:100',
            'avatar'         => 'sometimes|nullable|url',
            'gender'         => ['sometimes','nullable', Rule::in(['male', 'female', 'other'])],
            'birthday'       => 'sometimes|nullable|date',
            'loyalty_points' => 'sometimes|nullable|integer|min:0',
            'role'           => ['sometimes','nullable', Rule::in(['customer', 'admin', 'manager'])],
            'status'         => ['sometimes','nullable', Rule::in(['active', 'inactive', 'banned'])],
            'google_id'      => 'sometimes|nullable|numeric',
        ]);

        if(isset($validatedData['phone_number'])) {
            $user->phone_number = $validatedData['phone_number'];
        }
        if(array_key_exists('email', $validatedData)) {
            $user->email = $validatedData['email'];
        }
        if(isset($validatedData['password'])) {
            $user->password = Hash::make($validatedData['password']);
        }
        if(isset($validatedData['fullname'])) {
            $user->fullname = $validatedData['fullname'];
        }
        if(isset($validatedData['avatar'])) {
            $user->avatar = $validatedData['avatar'];
        }
        if(isset($validatedData['gender'])) {
            $user->gender = $validatedData['gender'];
        }
        if(isset($validatedData['birthday'])) {
            $user->birthday = $validatedData['birthday'];
        }
        if(isset($validatedData['loyalty_points'])) {
            $user->loyalty_points = $validatedData['loyalty_points'];
        }
        if(isset($validatedData['role'])) {
            $user->role = $validatedData['role'];
        }
        if(isset($validatedData['status'])) {
            $user->status = $validatedData['status'];
        }
        if(isset($validatedData['google_id'])) {
            $user->google_id = $validatedData['google_id'];
        }

        $user->save();

        return response()->json($user, 200);
    }



}
