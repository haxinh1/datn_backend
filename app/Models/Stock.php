<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Stock extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'total_amount',
        'status',
        'reason',
        'created_by',
        'updated_by'
    ];

    // Sửa lại quan hệ productStocks()
    public function productStocks()
    {
        return $this->hasMany(ProductStock::class, 'stock_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

        public static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $user = Auth::guard('sanctum')->user();
            $model->created_by = $user ? $user->id : 1;
            $model->updated_by = $user ? $user->id : 1;
        });

        static::updating(function ($model) {
            $user = Auth::guard('sanctum')->user();
            $model->updated_by = $user ? $user->id : 1;
        });
    }
}

