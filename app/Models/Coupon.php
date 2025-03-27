<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Coupon extends Model
{
    use HasFactory, SoftDeletes;


    protected $fillable = [
        'code',
        'title',
        'description',
        'discount_type',
        'discount_value',
        'usage_limit',
        'usage_count',
        'is_expired',
        'is_active',
        'start_date',
        'end_date',
        'rank',
        'coupon_type'

    ];


    protected $casts = [
        'discount_value' => 'float',
        'usage_limit' => 'integer',
        'usage_count' => 'integer',
        'is_expired' => 'boolean',
        'is_active' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'deleted_at' => 'datetime',
    ];



    public function users()
    {
        return $this->belongsToMany(User::class, 'coupon_users');
    }


    /*Kiem tra het han chua*/
    public function isExpired()
    {
        return $this->end_date && now()->greaterThan($this->end_date);
    }
}

