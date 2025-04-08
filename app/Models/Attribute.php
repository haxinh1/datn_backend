<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Attribute extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'is_variant',
        'is_active',
    ];
    public function attributeValues(){
        return $this->hasMany(AttributeValue::class);
    }
    public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }
}
