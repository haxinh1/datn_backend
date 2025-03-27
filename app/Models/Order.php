<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'user_id',
        'fullname',
        'email',
        'phone_number',
        'address',
        'total_amount',
        'used_points',
        'discount_points',
        'status_id',
        'payment_id',
        'coupon_id',
        'coupon_code',
        'coupon_description',
        'coupon_discount_type',
        'coupon_discount_value',
    ];

    /**
     * Quan hệ với chi tiết đơn hàng
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Quan hệ với phương thức thanh toán
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Quan hệ với trạng thái đơn hàng
     */
    public function status()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id');
    }

    /**
     * Quan hệ với lịch sử trạng thái đơn hàng
     */
    public function orderStatuses()
    {
        return $this->hasMany(OrderOrderStatus::class);
    }

    /**
     * Quan hệ với người dùng (nếu có)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function hasPurchasedProduct($userId, $productId)
    {
        return self::where('user_id', $userId)
            ->where('status_id', 7)
            ->whereHas('orderItems', function ($query) use ($productId) {
                $query->where('product_id', $productId);
            })
            ->exists();
    }

}
