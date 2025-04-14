<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BannedHistory extends Model
{
    use HasFactory;

    protected $table = 'banned_history'; 

    protected $fillable = [
        'user_id',
        'banned_by',
        'reason',
        'banned_at',
        'ban_expires_at',
        'unbanned_at',
        'created_at',
        'updated_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bannedBy()
    {
        return $this->belongsTo(User::class, 'banned_by');
    }

    public function isActive()
    {
        return is_null($this->unbanned_at);
    }
}
