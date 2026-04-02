<?php

namespace App\Models;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Customers extends Authenticatable
{
    use Notifiable;
    use HasApiTokens;
    protected $table = 'customers';
    protected $fillable = [
        'ho_ten',
        'email',
        'mat_khau',
        'so_dien_thoai',
        'ngay_sinh',
        'gioi_tinh',
        'diem_tich_luy',
        'trang_thai',
        'provider_id',
        'provider_name',
        'provider_token',
        'provider_refresh_token',
        'email_verified_at',
    ];

    protected $hidden = [
        'mat_khau',
        'provider_token',
        'provider_refresh_token'
    ];
    protected $casts = [
        'email_verified_at' => 'datetime',
        'ngay_sinh' => 'date',
        'trang_thai' => 'boolean',
        'gioi_tinh' => 'string',
        'diem_tich_luy' => 'integer'
    ];

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }
    public function invoices()
    {
        return $this->hasMany(Invoices::class , 'khach_hang_id');
    }
    public function tickets()
    {
        return $this->hasMany(Tickets::class , 'khach_hang_id');
    }

    public function promotions()
    {
        return $this->belongsToMany(Promotions::class, 'customer_promotion', 'customer_id', 'promotion_id')
                    ->withPivot('trang_thai', 'so_lan_da_dung', 'ngay_su_dung')
                    ->withTimestamps();
    }
    public function addLoyaltyPoints($points)
    {
        $this->diem_tich_luy += $points;
        $this->save();
        return $this;
    }
    //Trừ điểm tích lũy khi đổi quà
    public function redeemLoyaltyPoints($points)
    {
        if ($this->diem_tich_luy >= $points) {
            $this->diem_tich_luy -= $points;
            $this->save();
            return true;
        }
    }
    //Kiểm tra cấp độ thành viên
    public function getMembershipLevel()
    {
        if ($this->diem_tich_luy >= 1000) {
            return 'Vàng';
        }
        elseif ($this->diem_tich_luy >= 500) {
            return 'Bạc';
        }
        else {
            return 'Đồng';
        }
    }
}
