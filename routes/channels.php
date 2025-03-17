<?php

use App\Models\ChatSession;
use Illuminate\Support\Facades\Broadcast;

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

    if (auth()->check()) {
        // Nếu đã đăng nhập, kiểm tra quyền truy cập
        return $chatSession->customer_id === $user->id ||
            $chatSession->storeUsers()->where('user_id', $user->id)->exists();
    }

    // Nếu là guest, kiểm tra session và trả về dữ liệu guest
    if (session()->has('guest_phone') && session('guest_phone') === $chatSession->guest_phone) {
        return [
            'guest_phone' => $chatSession->guest_phone,
            'guest_name' => $chatSession->guest_name,
        ];
    }

    return false;
});
