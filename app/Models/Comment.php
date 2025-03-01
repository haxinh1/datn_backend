<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    protected $fillable = [
        'products_id',
        'users_id',
        'comments',
        'rating',
        'comment_date',
        'status', // Thêm cột status
    ];

    protected $casts = [
        'status' => 'integer', // Ép kiểu status thành integer
        'rating' => 'integer', // Nếu rating cũng là số thì ép kiểu luôn
        'comment_date' => 'datetime', // Nếu comment_date là DATETIME trong DB
    ];

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'users_id');
    }
}
