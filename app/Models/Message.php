<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $table = 'messages'; // Tên bảng

    protected $fillable = [
        'chat_session_id',
        'sender_id',
        'guest_phone',
        'guest_name',
        'message',
        'type',
        'is_read',
        'read_at',
        'sender_type'
    ];

    // Mặc định timestamps đã có (created_at, updated_at)

    protected $with = ['sender'];

    // Liên kết với phiên trò chuyện
    public function chatSession()
    {
        return $this->belongsTo(ChatSession::class, 'chat_session_id');
    }

    // Liên kết với người gửi (nếu có)
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
