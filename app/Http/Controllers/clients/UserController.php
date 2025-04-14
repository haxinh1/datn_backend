<?php

namespace App\Http\Controllers\clients;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Mail\VerifyEmail;
use App\Models\UserAddress;
use App\Mail\ResetPasswordMail;
use App\Models\PasswordResetTokens;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class UserController extends Controller
{

    public function register(Request $request)
    {

        $validator = Validator::make($request->all(), [
            'phone_number' => ['required', 'regex:/^0[0-9]{9}$/', 'unique:users,phone_number'],
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:6|confirmed',
            'fullname' => 'required|string|max:100',
            'avatar' => 'nullable|string',
            'gender' => 'nullable|in:male,female,other',
            'birthday' => 'nullable|date',
            'address' => 'nullable|string',
            'detail_address' => 'nullable|string',
            'ProvinceID' => 'nullable|string',
            'DistrictID' => 'nullable|string',
            'WardCode' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Đăng ký thất bại',
                'errors' => $validator->errors()
            ], 400);
        }

        try {
            $user = User::create([
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'fullname' => $request->fullname,
                'avatar' => $request->avatar,
                'gender' => $request->gender,
                'birthday' => $request->birthday,
                'role' => 'customer',
                'status' => 'inactive',
                'total_spent' => 0,
                'rank_points' => 0,
                'rank' => 'Thành Viên',
            ]);

         if($request->filled('address') && $request->filled('detail_address')) {
            UserAddress::create([
                'user_id' => $user->id,
                'address' => $request->address,
                'detail_address' => $request->detail_address,
                'ProvinceID' => $request->ProvinceID,
                'DistrictID' => $request->DistrictID,
                'WardCode' => $request->WardCode,
                'id_default' => true,
            ]);
         }
        


            $code = random_int(100000, 999999);
            $expiresTime = Carbon::now()->addMinutes(2); 

            DB::table('email_verification_codes')->insert([
                'user_id' => $user->id,
                'code' => $code,
                'created_at' => now(),
                'updated_at' => now(),
                'expires_at' => $expiresTime, 
            ]);

            // Gửi email xác nhận
            Mail::to($user->email)->send(new VerifyEmail($user, $code));

            return response()->json([
                'message' => 'Đăng ký thành công. Vui lòng kiểm tra email của bạn để xác nhận tài khoản.',
                'user' => $user
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Đăng ký thất bại',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function resendVerificationCode(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);
    

        $user = User::where('email', $request->email)->first();
    
        if (!$user) {
            return response()->json(['message' => 'Người dùng không tồn tại!'], 404);
        }
    

        $code = random_int(100000, 999999);
        $expiresTime = Carbon::now()->addMinutes(2);
    

        DB::table('email_verification_codes')->where('user_id', $user->id)->delete();
    

        DB::table('email_verification_codes')->insert([
            'user_id' => $user->id,
            'code' => $code,
            'created_at' => now(),
            'updated_at' => now(),
            'expires_at' => $expiresTime,
        ]);
    
       
        Mail::to($user->email)->send(new VerifyEmail($user, $code));
    
        return response()->json([
            'message' => 'Mã xác minh đã được gửi lại. Vui lòng kiểm tra email của bạn.',
        ], 200);
    }
    
    public function verifyEmail(Request $request)
    { 
        {
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'verification_code' => 'required|numeric|digits:6',
            ]);

            // Tìm user theo email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'Người dùng không tồn tại!'], 404);
            }

            // Tìm mã xác nhận
            $verification_code =  DB::table('email_verification_codes')->where('user_id', $user->id)
                ->where('code', $request->verification_code)
                ->first();

            if (!$verification_code) {
                return response()->json(['message' => 'Mã xác nhận không hợp lệ '], 400);
            }

            if (Carbon::now()->gt(Carbon::parse($verification_code->expires_at))) {
                return response()->json(['message' => 'Mã xác nhận đã hết hạn!'], 400);
            }

            $user->status = 'active';
            $user->save();

            DB::table('email_verification_codes')->where('code', $request->verification_code)->delete();


            // $verification_code->delete();

            return response()->json(['message' => 'Xác minh thành công . Tài khoản đã được kích hoạt!'], 200);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'phone_number' => 'required',
            'password' => 'required',
        ]);

        $user = User::where('phone_number', $request->phone_number)->orWhere('email', $request->phone_number)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Thông tin đăng nhập không đúng'], 401);
        }

        if ($user->status === 'inactive') {
            return response()->json([
                'message' => 'Tài khoản của bạn đã dừng hoạt động'
            ], 403);
        }
        if ($user->status === 'banned') {
            // Lấy bản ghi khóa mới nhất
            $ban = \App\Models\BannedHistory::where('user_id', $user->id)
                ->latest('banned_at')
                ->first();

            if ($ban) {
                $now = Carbon::now();

                // Nếu đã mở khóa
                if ($ban->unbanned_at) {
                    $user->update(['status' => 'active']);
                } else {
                    // Nếu chưa mở khóa, kiểm tra thời gian hết hạn
                    $expireTime = $ban->ban_expires_at ? Carbon::parse($ban->ban_expires_at) : null;

                    if ($expireTime && $now->gte($expireTime)) {
                        // Hết thời gian khóa
                        $ban->update(['unbanned_at' => $now]);
                        $user->update(['status' => 'active']);
                    } else {
                    
                        $secondsLeft = $expireTime ? $now->diffInSeconds($expireTime) : null;
                        if ($secondsLeft) {
                            if ($secondsLeft <= 50 * 60) { 
                                $minutes = floor($secondsLeft / 60);
                                $remainingSeconds = $secondsLeft % 60;
                                $message = "Tài khoản của bạn đang bị khóa hãy thử lại sau $minutes phút $remainingSeconds giây";
                            } else {
                                $minutesLeft = $secondsLeft / 60;
                                $hoursLeft = floor($minutesLeft / 60);
                                $remainingMinutes = $minutesLeft % 60;
                                $message = "Tài khoản của bạn đang bị khóa hãy thử lại sau $hoursLeft giờ";
                                if ($remainingMinutes > 0) {
                                    $message .= " $remainingMinutes phút";
                                }
                            }
                        } else {
                            $message = "Tài khoản của bạn đang bị khóa";
                        }

                        return response()->json([
                            'message' => $message,
                            'expires_at' => $expireTime ? $expireTime->toDateTimeString() : null,
                        ], 403);
                    }
                }
            }
        }

        $token = $user->createToken('customer_token')->plainTextToken;

        return response()->json([
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'fullname' => $user->fullname,
                'avatar' => $user->avatar,
                'role' => $user->role,
            ],
        ]);
    }

    public function logout(Request $request)
    {

        $request->user()->tokens()->delete();
        session()->invalidate();
        session()->regenerateToken();

        return response()->json(['message' => 'Đăng xuất thành công']);
    }

    public function changePassword(Request $request , $id)
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

    public function forgotPassword(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users,email'
        ]);

        $token = Str::random(60);


        DB::table('password_reset_tokens')->insert([
            'email' => $request->email,
            'token' => Hash::make($token),
            'created_at' => now(),
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user->role == 'admin' || $user->role == 'manager') {

            Mail::to($request->email)->send(new ResetPasswordMail($token, 'admin'));
        } else {

            Mail::to($request->email)->send(new ResetPasswordMail($token, 'customer'));
        }

        // Mail::to($request->email)->send(new ResetPasswordMail($token));

        return response()->json([
            'message' => 'Vui lòng kiểm tra email của bạn'
        ], 200);
    }



    public function resetPassword(Request $request)
    {

        $request->validate([
            'email' => 'required|email|exists:users,email',
            'token' => 'required',
            'password' => 'required|string|min:6|confirmed',
        ]);


        $passwordReset = DB::table('password_reset_tokens')
            ->where('email', $request->email)
            ->first();

        if (!$passwordReset || !Hash::check($request->token, $passwordReset->token)) {
            return response()->json([
                'message' => 'Token không hợp lệ hoặc đã hết hạn'
            ], 400);
        }


        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();

        DB::table('password_reset_tokens')->where('email', $request->email)->delete();

        return response()->json([
            'message' => 'Mật khẩu đã được đặt lại thành công'
        ], 200);
    }
}
