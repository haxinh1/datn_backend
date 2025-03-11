<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'product_id', 'product_variant_id', 'quantity', 'price'
    ];

    /**
     * 📌 Quan hệ: Một mục giỏ hàng thuộc về một người dùng (nếu đã đăng nhập)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 📌 Quan hệ: Một mục giỏ hàng thuộc về một sản phẩm
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * 📌 Quan hệ: Một mục giỏ hàng thuộc về một biến thể sản phẩm (nếu có)
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
