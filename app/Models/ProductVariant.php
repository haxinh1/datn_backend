<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductVariant extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'sku',
        'sell_price',
        'sale_price',
        'is_active',
        'sale_price_start_at',
        'sale_price_end_at',
        'thumbnail',
    ];
    public function attributeValueProductVariants()
    {
        return $this->hasMany(AttributeValueProductVariant::class);
    }
}
