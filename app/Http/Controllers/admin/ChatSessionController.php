<?php

namespace App\Http\Controllers\admin;

use App\Http\Controllers\Controller;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatSessionController extends Controller
{
    /**
     * Tạo phiên trò chuyện mới
     */
    public function createSession(Request $request)
    {
        $user = Auth::guard('sanctum')->user();
        $rules = $user
            ? [] // Nếu đã đăng nhập, không cần validate guest_phone và guest_name
            : [
                'guest_phone' => 'required|string|max:15',
                'guest_name' => 'required|string|max:255',
            ];

        $request->validate($rules);

        $chatSession = ChatSession::create([
            'customer_id' => $user ? $user->id : null,
            'guest_phone' => $user ? null : $request->guest_phone,
            'guest_name' => $user ? null : $request->guest_name,
            'status' => 'open',
            'created_date' => now()
        ]);

        return response()->json(['message' => 'Chat session created', 'chat_session' => $chatSession], 201);
    }

    /**
     * Lấy danh sách phiên chat theo nhân viên hoặc khách
     */
    public function getSessions(Request $request)
    {

        $user = Auth::guard('sanctum')->user();
        if ($user) {
            // Nếu là nhân viên, lấy tất cả chat đang mở
            if (!($user->role === "customer")) {
                $sessions = ChatSession::with('customer')
                    ->get();
            } else {
                // Nếu là khách hàng đã đăng nhập, chỉ lấy phiên của họ
                $sessions = ChatSession::with('customer')
                    ->where('customer_id', $user->id)
                    ->get();
            }
        } else {
            // Nếu là khách vãng lai, lấy phiên theo số điện thoại
            $sessions = ChatSession::where('guest_phone', $request->guest_phone)->get();
        }

        return response()->json(['chat_sessions' => $sessions]);
    }

    /**
     * Đóng phiên trò chuyện
     */
    public function closeSession($id)
    {
        $chatSession = ChatSession::findOrFail($id);
        $chatSession->update(['status' => 'closed', 'closed_date' => now()]);

        return response()->json(['message' => 'Chat session closed']);
    }
}
