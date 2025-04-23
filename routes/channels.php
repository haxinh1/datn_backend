<?php

use App\Models\ChatSession;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});



Broadcast::channel('chat.{chatSessionId}', function ($user, $chatSessionId) {


    $chatSession = ChatSession::where('id', $chatSessionId)->first();
    if (!$chatSession) {

        return false;
    }

    $auth = Auth::guard('sanctum')->user();
    if ($auth) {
        $allowed = $chatSession->customer_id === $auth->id || $auth->role != "customer";

        return $allowed;
    }

    $guestPhone = request()->input('guest_phone') ?? session('guest_phone');
    if ($guestPhone && $guestPhone === $chatSession->guest_phone) {

        return [
            'guest_phone' => $chatSession->guest_phone,
            'guest_name' => $chatSession->guest_name,
        ];
    }

    return false;
});

Broadcast::channel('chat-sessions', function ($user) {
    Log::info('Broadcast auth attempt', ['user' => $user]);
    return true;
});
