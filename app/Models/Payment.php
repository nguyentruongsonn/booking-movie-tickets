<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_FAILED = 0;
    const STATUS_PENDING = 1;
    const STATUS_COMPLETED = 2;
    const STATUS_REFUNDED = 3;

    protected $fillable = [
        'order_id',
        'method',
        'transaction_code',
        'amount',
        'status',
        'payload',
        'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'status' => 'integer',
        'payload' => 'json',
        'paid_at' => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
