<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tickets extends Model
{
    protected $fillable = [
        'ma_ve',
        'suat_chieu_id',
        'ghe_id',
        'khach_hang_id',
        'order_id',
        'hoa_don_id',
        'gia_goc',
        'gia_ban',
        'trang_thai',
        'ngay_gio_dat',
    ];

    protected $casts = [
        'gia_goc' => 'decimal:2',
        'gia_ban' => 'decimal:2',
        'ngay_gio_dat' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'khach_hang_id');
    }

    public function showtime()
    {
        return $this->belongsTo(Showtimes::class, 'suat_chieu_id');
    }

    public function seat()
    {
        return $this->belongsTo(Seats::class, 'ghe_id');
    }

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'hoa_don_id');
    }

    public function order()
    {
        return $this->belongsTo(Orders::class, 'order_id');
    }
}
