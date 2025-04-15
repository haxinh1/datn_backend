<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'brand_id',
        'name',
        'name_link',
        'slug',
        'views',
        'total_sales',
        'content',
        'thumbnail',
        'sku',
        'price',
        'sell_price',
        'sale_price',
        'sale_price_start_at',
        'sale_price_end_at',
        'is_active',
    ];
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_products');
    }

    public function galleries()
    {
        return $this->hasMany(ProductGalleries::class);
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function atributeValueProduct()
    {
        return $this->hasMany(AttributeValueProduct::class);
    }
    public function productStocks()
    {
        return $this->hasMany(ProductStock::class, 'stock_id');
    }
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_products', 'product_id', 'attribute_value_id');
    }
    public function viewedByUsers()
    {
        return $this->belongsToMany(User::class, 'viewed_products')
                    ->withTimestamps();
    }

    public function viewedProducts()
    {
        return $this->hasMany(ViewedProduct::class);
    }
    public function comments(){
        return $this->hasMany(Comment::class,'products_id');
    }

}
