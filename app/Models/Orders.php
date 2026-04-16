<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Orders extends Model
{
    use HasFactory;

    protected $table = 'orders';

    protected $fillable = [
        'ma_don_hang',
        'order_code',
        'payment_provider',
        'customer_id',
        'suat_chieu_id',
        'tong_tien',
        'payload',
        'trang_thai',
        'payment_status',
        'checkout_url',
        'paid_at',
        'cancelled_at',
        'expired_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'tong_tien' => 'decimal:2',
        'created_at' => 'datetime',
        'paid_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }

    public function showtime()
    {
        return $this->belongsTo(Showtimes::class, 'suat_chieu_id');
    }

    public function tickets()
    {
        return $this->hasMany(Tickets::class, 'order_id');
    }

    public function invoice()
    {
        return $this->hasOne(Invoices::class, 'order_id');
    }
}
