<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundDetail extends Model
{
    protected $fillable = [
        'order_id', 'order_return_id', 'note', 'employee_evidence', 'status'
    ];

    public function order() {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn() {
        return $this->belongsTo(OrderReturn::class);
    }
}

