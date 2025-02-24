<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderOrderStatus extends Model
{
    use HasFactory;

    protected $table = 'order_order_statuses';

    protected $fillable = [
        'order_id',
        'order_status_id',
        'modified_by',
        'note',
        'employee_evidence',
    ];

    protected $casts = [
        'employee_evidence' => 'array',
    ];

    public $timestamps = true;

    // Liên kết với Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Liên kết với OrderStatus
    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    // Liên kết với User (người cập nhật trạng thái)
    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
