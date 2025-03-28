<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RefundDetail extends Model
{
    protected $fillable = [
        'order_id',
        'order_return_id',
        'note',
        'employee_evidence',
        'status'
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function orderReturn()
    {
        return $this->belongsTo(OrderReturn::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class); // Giả sử có quan hệ `hasMany` với bảng `attribute_values`
    }
}
