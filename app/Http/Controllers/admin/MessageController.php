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
        $request->validate([
            'chat_session_id' => 'required|exists:chat_sessions,id',
            'message' => 'nullable|string', // Cho phép null
            'media' => 'nullable|array',
            'media.*.url' => 'required_with:media|string|url',
            'media.*.type' => 'required_with:media|in:image,video',
            'guest_phone' => 'required_if:user_role,guest|nullable|string',
            'guest_name' => 'required_if:user_role,guest|nullable|string',
        ], [
            'message.required_without' => 'Bạn phải nhập tin nhắn hoặc gửi media.',
            'media.required_without' => 'Bạn phải nhập tin nhắn hoặc gửi media.',
        ]);

        if (empty($request->message) && empty($request->media)) {
            return response()->json(['error' => 'Bạn phải nhập tin nhắn hoặc gửi media.'], 422);
        }


        // Tìm phiên chat theo ID
        $chatSession = ChatSession::find($request->chat_session_id);

        // Nếu không tìm thấy phiên chat, trả về lỗi 404
        if (!$chatSession) {
            return response()->json(['error' => 'Chat session not found'], 404);
        }

        // Lấy thông tin người gửi từ hệ thống xác thực
        $userAuth = Auth::guard('sanctum')->user();
        $senderId =  $userAuth ? $userAuth->id : null;
        $userRole = $userAuth ? $userAuth->role : 'guest'; // Nếu chưa đăng nhập, mặc định là khách

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
            'message' => $request->has('message') ? $request->message : "",
            'type' => $request->type ?? 'text', // Loại tin nhắn, mặc định là "text"
        ]);


        if ($request->has('media') && is_array($request->media)) {
            foreach ($request->media as $mediaItem) {
                if (isset($mediaItem['url']) && isset($mediaItem['type'])) {
                    $message->media()->create([
                        'type' => $mediaItem['type'], // 'image' hoặc 'video'
                        'url' => $mediaItem['url'],
                    ]);
                }
            }
        }

        $message->load(['sender', 'media']);


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
        $messages = Message::with(['media', 'sender'])
            ->where('chat_session_id', $chatSessionId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($message) {
                $messageArray = $message->toArray();
                $messageArray['user'] = $message->sender; // Thêm trường user
                return $messageArray;
            });

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
