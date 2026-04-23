<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceRule extends Model
{
    protected $table = 'price_rules';

    protected $fillable = [
        'ten_quy_tac',
        'loai_ngay',
        'khung_gio',
        'gio_bat_dau',
        'gio_ket_thuc',
        'he_so_gia',
        'trang_thai',
    ];

    protected $casts = [
        'gio_bat_dau' => 'datetime',
        'gio_ket_thuc' => 'datetime',
        'he_so_gia'  => 'float',
        'trang_thai' => 'boolean',
    ];

    public function scopeApDung($query, string $ngay, string $gio)
    {
        return $query->where('trang_thai', true)
            ->where('loai_ngay', $ngay)
            ->where('gio_bat_dau', '<=', $gio)
            ->where('gio_ket_thuc', '>=', $gio);
    }
}
