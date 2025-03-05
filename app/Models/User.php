<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
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
    ];
    public function addresses()
    {
        return $this->hasMany(UserAddress::class);
    }

    public function address()
{
    return $this->hasOne(UserAddress::class, 'user_id'); // 'user_id' là khóa ngoại trong bảng useraddress
}
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];
}
