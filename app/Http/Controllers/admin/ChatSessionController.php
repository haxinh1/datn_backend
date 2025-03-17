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
        $rules = Auth::check()
            ? [] // Nếu đã đăng nhập, không cần validate guest_phone và guest_name
            : [
                'guest_phone' => 'required|string|max:15',
                'guest_name' => 'required|string|max:255',
            ];

        $request->validate($rules);

        $chatSession = ChatSession::create([
            'customer_id' => Auth::check() ? Auth::id() : null,
            'guest_phone' => Auth::check() ? null : $request->guest_phone,
            'guest_name' => Auth::check() ? null : $request->guest_name,
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
        if (Auth::check()) {
            // Nếu là nhân viên, lấy tất cả chat đang mở
            if (Auth::user()->is_employee) {
                $sessions = ChatSession::where('status', 'open')->get();
            } else {
                // Nếu là khách hàng đã đăng nhập, chỉ lấy phiên của họ
                $sessions = ChatSession::where('customer_id', Auth::id())->get();
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
