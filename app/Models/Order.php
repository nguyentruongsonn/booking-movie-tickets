<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $table = 'orders';

    // Trạng thái đơn hàng (status)
    public const STATUS_CANCELLED = 0;
    public const STATUS_PENDING   = 1;
    public const STATUS_PAID      = 2;
    public const STATUS_REFUNDED  = 3;
    public const STATUS_EXPIRED   = 4;

    protected $fillable = [
        'code',
        'gateway_order_code',
        'payment_provider',
        'user_id',
        'showtime_id',
        'total_amount',
        'payload',
        'status',
        'payment_status',
        'checkout_url',
        'paid_at',
        'cancelled_at',
        'expired_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'payload'      => 'json',
        'status'       => 'integer',
        'paid_at'      => 'datetime',
        'cancelled_at' => 'datetime',
        'expired_at'   => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function showtime()
    {
        return $this->belongsTo(Showtime::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
