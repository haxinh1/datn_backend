<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class GoogleController extends Controller
{
  
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }


    public function handleGoogleCallback()
{
    try {
        $googleUser = Socialite::driver('google')->user();
    } catch (\Exception $e) {
        return redirect('/login')->with('error', 'Xác thực Google thất bại.');
    }


    $user = User::where('email', $googleUser->getEmail())->first();

    if ($user) {
   
        Auth::login($user);
   
    } else {
      
        $newUser = User::create([
            'google_id'     => $googleUser->id,
            'phone_number'  => '0' . rand(100000000, 999999999),
            'password'      => bcrypt(Str::random(16)),
            'email'         => $googleUser->email,
            'fullname'      => $googleUser->name,
            'avatar'        => $googleUser->avatar,
            'status'        => 'active',
            'role'          => 'customer',
            'rank'          => 'Thành Viên',
            'rank_points'   => 0,
            'loyalty_points' => 0,
            'total_spent'   => 0,
            'verified_at'   => now(),
        ]);

        Auth::login($newUser);
        $token = $newUser->createToken('access_token')->plainTextToken;
      return response()->json([
            'user' => $newUser,
            'token' => $token,
        ]);
    }
}


}
