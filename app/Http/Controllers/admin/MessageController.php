<?php

namespace App\Http\Controllers\admin;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\ChatSession;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MessageController extends Controller
{
    /**
     * Gửi tin nhắn trong một phiên chat
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendMessage(Request $request)
    {
        // Tìm phiên chat theo ID
        $chatSession = ChatSession::find($request->chat_session_id);

        // Nếu không tìm thấy phiên chat, trả về lỗi 404
        if (!$chatSession) {
            return response()->json(['error' => 'Chat session not found'], 404);
        }

        // Lấy thông tin người gửi từ hệ thống xác thực
        $senderId = Auth::check() ? Auth::id() : null;
        $userRole = Auth::check() ? Auth::user()->role : 'guest'; // Nếu chưa đăng nhập, mặc định là khách

        // Xác định loại người gửi dựa vào vai trò
        if (in_array($userRole, ['admin', 'manager'])) {
            // Nhân viên chỉ có thể gửi tin nhắn khi phiên chat đang mở
            if ($chatSession->status !== 'open') {
                return response()->json(['error' => 'Chat session is closed'], 403);
            }
            $senderType = 'store'; // Nhóm nhân viên, quản lý, admin thuộc loại "store"
        } elseif ($userRole === 'customer') {
            // Khách hàng chỉ có thể gửi tin nhắn nếu họ là chủ của phiên chat đó
            if ($chatSession->customer_id !== $senderId) {
                return response()->json(['error' => 'You do not have permission to send messages in this session'], 403);
            }
            $senderType = 'customer';
        } else {
            // Người dùng chưa đăng nhập sẽ được xem là khách vãng lai (guest)
            $senderType = 'guest';
        }

        // Tạo một tin nhắn mới và lưu vào database
        $message = Message::create([
            'chat_session_id' => $chatSession->id,
            'sender_id' => $senderId, // ID người gửi (nếu có)
            'sender_type' => $senderType, // Loại người gửi: store, customer, guest
            'guest_phone' => $userRole === 'guest' ? $request->guest_phone : null, // Nếu là khách thì lưu số điện thoại
            'guest_name' => $userRole === 'guest' ? $request->guest_name : null, // Nếu là khách thì lưu tên
            'message' => $request->message, // Nội dung tin nhắn
            'type' => $request->type ?? 'text', // Loại tin nhắn, mặc định là "text"
        ]);


        // Phats ra event để client bắt web socket
        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['message' => 'Message sent', 'data' => $message]);
    }

    /**
     * Lấy tất cả tin nhắn trong một phiên chat
     *
     * @param int $chatSessionId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getMessages($chatSessionId)
    {
        // Truy vấn tất cả tin nhắn của một phiên chat theo ID, sắp xếp theo thời gian tạo (từ cũ đến mới)
        $messages = Message::where('chat_session_id', $chatSessionId)
            ->orderBy('created_at', 'asc')
            ->get();

        // Trả về danh sách tin nhắn dưới dạng JSON
        return response()->json(['messages' => $messages]);
    }

    /**
     * Đánh dấu tin nhắn là đã đọc
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsRead($id)
    {
        // Tìm tin nhắn theo ID, nếu không có sẽ báo lỗi 404
        $message = Message::findOrFail($id);

        // Cập nhật trạng thái tin nhắn là đã đọc
        $message->update([
            'is_read' => true, // Đánh dấu là đã đọc
            'read_at' => now() // Ghi lại thời gian đọc
        ]);

        // Trả về phản hồi JSON xác nhận tin nhắn đã được đánh dấu là đọc
        return response()->json(['message' => 'Message marked as read']);
    }
}
