<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderCancel extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'bank_account_number',
        'bank_name',
        'bank_qr',
        'reason',
        'status_id',
        'refund_proof',
    ];
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }
}
