<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'user_id', 'payment_id', 'phone_number', 'email', 'fullname', 
        'address', 'total_amount', 'is_paid', 'coupon_id', 'coupon_code'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function orderStatuses()
    {
        return $this->hasMany(OrderOrderStatus::class);
    }
}
