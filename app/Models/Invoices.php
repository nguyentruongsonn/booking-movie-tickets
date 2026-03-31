<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HoaDon extends Model
{
    protected $table = 'hoa_don';
    
    protected $fillable = [
        'ma_hoa_don',
        'khach_hang_id',
        'nhan_vien_id',
        'khuyen_mai_id',
        'lich_chieu_id',
        'ngay_lap',
        'tong_tien_goc',
        'giam_gia',
        'tong_tien',
        'diem_su_dung',
        'diem_tich_luy',
        'phuong_thuc_thanh_toan',
        'trang_thai',
    ];
    
    protected $casts = [
        'ngay_lap' => 'datetime',
        'tong_tien_goc' => 'float',
        'giam_gia' => 'float',
        'tong_tien' => 'float',
        'diem_su_dung' => 'integer',
        'diem_tich_luy' => 'integer',
    ];
    
    public function customer()
    {
        return $this->belongsTo(Customers::class, 'khach_hang_id');
    }
    
    public function employee()
    {
        return $this->belongsTo(Employees::class, 'nhan_vien_id');
    }
    
    public function promotion()
    {
        return $this->belongsTo(Promotions::class, 'khuyen_mai_id');
    }
    
    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetails::class, 'hoa_don_id');
    }
    
    public function tickets()
    {
        return $this->hasMany(Tickets::class, 'hoa_don_id');
    }
}