<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',  
        'name',
        'logo',
        'is_active'
    ];

    public function parent()
    {
        return $this->belongsTo(Payment::class, 'parent_id');
    }
}
