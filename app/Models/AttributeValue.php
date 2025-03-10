<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    use HasFactory;
    public $timestamps = false;
    protected $fillable = [
        'attribute_id',
        'value',
        'is_active',
    ];
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'attribute_value_product', 'attribute_value_id', 'product_id');
    }

    public function productVariants()
    {
        return $this->belongsToMany(AttributeValueProductVariant::class, 'attribute_value_product_variant', 'attribute_value_id', 'product_variant_id');
    }  
}
