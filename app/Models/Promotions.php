<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KhuyenMai extends Model
{
    protected $table = 'khuyen_mai';
    
    protected $fillable = [
        'ma',
        'ten',
        'mo_ta',
        'loai_giam_gia',
        'gia_tri_giam',
        'don_toi_thieu',
        'ngay_bat_dau',
        'ngay_ket_thuc',
        'so_lan_da_dung',
        'trang_thai',
    ];
    
    protected $casts = [
        'gia_tri_giam' => 'float',
        'don_toi_thieu' => 'float',
        'ngay_bat_dau' => 'datetime',
        'ngay_ket_thuc' => 'datetime',
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