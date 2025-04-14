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
        return Socialite::driver('google')->with(['prompt' => 'select_account'])->redirect();
    }
    
    public function handleGoogleCallback()
    {

        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {

            Auth::login($user);

            $user->update([
                'google_id' => $googleUser->id,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'fullname' => $googleUser->name,
                'avatar' => $googleUser->avatar,
                'status' => 'active',
            ]);

            $token = $user->createToken('token')->plainTextToken;
            
            return redirect()->to('http://localhost:5173/google-callback?token=' . $token . '&user=' . urlencode(json_encode($user)));
       
        } else {
            $newUser = User::create([
                'google_id' => $googleUser->id,
                'phone_number' => '0' . rand(100000000, 999999999),
                'password' => bcrypt(Str::random(16)),
                'email' => $googleUser->email,
                'fullname' => $googleUser->name,
                'avatar' => $googleUser->avatar,
                'status' => 'active',
                'role' => 'customer',
                'rank' => 'Thành Viên',
                'rank_points' => 0,
                'loyalty_points' => 0,
                'total_spent' => 0,
                'verified_at' => now(),
            ]);
            Auth::login($newUser);
            $token = $newUser->createToken('token')->plainTextToken;
            return redirect()->to('http://localhost:5173/google-callback?token=' . $token . '&user=' . urlencode(json_encode($newUser)));
        }
    }
}
