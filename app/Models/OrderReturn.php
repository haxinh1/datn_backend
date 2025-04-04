<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderReturn extends Model
{
    use HasFactory;

    // Chỉ định bảng
    protected $table = 'order_returns';

    // Các thuộc tính có thể được gán đại trà
    protected $fillable = [
        'order_id',
        'product_id',
        'product_variant_id',
        'quantity_returned',
        'reason',
        'employee_evidence',
        'status_id',
        'price',
    ];


    // Quan hệ với bảng Orders
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    // Quan hệ với bảng Products
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Quan hệ với bảng ProductVariants
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
    // Quan hệ với bảng OrderStatus
    public function orderStatus()
    {
        return $this->belongsTo(OrderStatus::class, 'status_id'); // Liên kết với bảng order_statuses
    }
    public function attributeValues()
    {
        return $this->hasMany(AttributeValue::class); // Giả sử có quan hệ `hasMany` với bảng `attribute_values`
    }
}
