<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatSession extends Model
{
    use HasFactory;

    protected $table = 'chat_sessions'; // Tên bảng

    protected $fillable = [
        'customer_id',
        'guest_phone',
        'guest_name',
        'employee_id',
        'status',
        'created_date',
        'closed_date',
    ];

    public $timestamps = false; // Vì bảng không có `created_at`, `updated_at`

    // Liên kết với khách hàng (nếu đã đăng nhập)
    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    // Liên kết với nhân viên hỗ trợ
    public function employee()
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    // Liên kết với tất cả tin nhắn trong phiên chat
    public function messages()
    {
        return $this->hasMany(Message::class, 'chat_session_id');
    }
}
