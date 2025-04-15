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
        'total_product_amount',
        'used_points',
        'discount_points',
        'shipping_fee',
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

    // Hàm check user đã từng dùng chauw
    // true là user này đã dùng voucher này 1 lần
    // false là user này chưa tung dùng voucher này 1 lần
    public static function hasUsedCoupon($userId, $couponId)
    {
        return self::where('user_id', $userId)
            ->where('coupon_id', $couponId)
            ->exists();
    }

    public function order_returns()
    {
        return $this->hasMany(OrderReturn::class, 'order_id', 'id');
    }

    public static function getRemainingCommentCountByProduct($userId, $productId)
    {
        // 1. Tổng số lượng đã mua của sản phẩm này (đơn hàng hoàn thành)
        $purchasedQty = OrderItem::whereHas('order', function ($query) use ($userId) {
            $query->where('user_id', $userId)
                ->where('status_id', 7);
        })
            ->where('product_id', $productId)
            ->sum('quantity');

        // 2. Số lượt đã bình luận cho sản phẩm này
        $commentedQty = Comment::where('user_id', $userId)
            ->where('product_id', $productId)
            ->count();

        // 3. Tính số lượt còn lại có thể bình luận
        $remaining = $purchasedQty - $commentedQty;

        return max($remaining, 0); // Không trả số âm
    }

}
