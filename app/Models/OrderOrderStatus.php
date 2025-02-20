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
        'customer_confirmation'
    ];

    protected $casts = [
        'employee_evidence' => 'array',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'order_status_id');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }
}
