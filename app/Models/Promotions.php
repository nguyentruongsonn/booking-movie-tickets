<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotions extends Model
{
    protected $table = 'promotions';

    protected $fillable = [
        'ma_khuyen_mai',
        'ten_khuyen_mai',
        'loai_khuyen_mai',
        'mo_ta',
        'loai_giam_gia',
        'gia_tri_giam',
        'don_toi_thieu',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'so_lan_su_dung',
        'so_lan_su_dung_moi_ngay',
        'trang_thai',
    ];

    protected $casts = [
        'gia_tri_giam' => 'float',
        'don_toi_thieu' => 'float',
        'ngay_bat_dau' => 'datetime',
        'ngay_ket_thuc' => 'datetime',
        'so_lan_su_dung' => 'integer',
        'so_lan_su_dung_moi_ngay' => 'integer',
        'trang_thai' => 'boolean',
    ];

    public function hoaDons()
    {
        return $this->hasMany(HoaDon::class, 'khuyen_mai_id');
    }

    public function isValid()
    {
        return $this->trang_thai &&
               now()->between($this->ngay_bat_dau, $this->ngay_ket_thuc);
    }
}
