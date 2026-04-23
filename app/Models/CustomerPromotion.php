<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPromotion extends Model
{
    protected $table = 'customer_promotion';

    protected $fillable = [
        'customer_id',
        'promotion_id',
        'trang_thai',
        'ngay_su_dung',
        'booking_id',
        'order_id',
        'invoice_id',
        'so_lan_da_dung',
        'gia_tri_giam',
    ];

    protected $casts = [
        'trang_thai'    => 'integer',
        'ngay_su_dung'  => 'datetime',
        'so_lan_da_dung' => 'integer',
        'gia_tri_giam'  => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function promotion()
    {
        return $this->belongsTo(Promotion::class, 'promotion_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }
}
