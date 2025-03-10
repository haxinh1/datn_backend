<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Các trường có thể gán hàng loạt
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'google_id',
        'phone_number',
        'email',
        'password',
        'fullname',
        'avatar',
        'gender',
        'birthday',
        'loyalty_points',
        'role',
        'status',
        'verified_at',
        'total_spent',
        'rank',
    ];

    /**
     * Ẩn các thuộc tính khi serialize
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Ép kiểu các thuộc tính
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Quan hệ: Một user có nhiều địa chỉ
     */
    public function addresses()
    {
        return $this->hasMany(UserAddress::class, 'user_id');
    }
    public function address()
    {
        return $this->hasOne(UserAddress::class, 'user_id');
    }


    /**
     * Lấy địa chỉ mặc định của user
     */
    public function defaultAddress()
    {
        return $this->hasOne(UserAddress::class, 'user_id')->where('id_default', true);
    }
    
}
