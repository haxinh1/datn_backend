<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'address',
        'detail_address',
        'ProvinceID',
        'DistrictID',
        'WardCode',
        'id_default',

    ];

    public $timestamps = false;

    /**
     * Map cột id_default thành boolean để dễ xử lý trong code
     */
    protected $casts = [
        'id_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
