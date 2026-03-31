<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
class Employees extends Model
{
    //
    use HasApiTokens;
    protected $fillable = [
        'ho_ten',
        'email',
        'mat_khau',
        'so_dien_thoai',
        'ngay_sinh',
        'gioi_tinh',
        'dia_chi',
        'chuc_vu',
        'ngay_vao_lam',
        'trang_thai',
        'email_verified_at',
    ];
    protected $casts = [
        'ngay_sinh' => 'date',
        'ngay_vao_lam' => 'date',
        'chuc_vu' => 'string',
        'trang_thai' => 'boolean',
        'email_verified_at' => 'datetime',
        'gioi_tinh' => 'string'
    ];
    protected $hiddien = [
        'mat_khau',
        'remember_token',
    ];

    public function getAuthPassword()
    {
        return $this->mat_khau;
    }
    public function invoices()
    {
        return $this->hasMany(Invoices::class , 'nhan_vien_id');
    }
    public function hasRole($role)
    {
        return $this->chuc_vu === $role;
    }
    public function isAdmin()
    {
        return $this->hasRole('Admin');
    }
    public function isQuanLy()
    {
        return $this->hasRole('Quản lý');
    }
    public function isBanVe()
    {
        return $this->hasRole('Bán vé');
    }
    public function isQuayNuoc()
    {
        return $this->hasRole('Quầy nước');
    }
    public function isSoatVe()
    {
        return $this->hasRole('Soát vé');
    }
    public function hasAdminAccess()
    {
        return $this->isAdmin() || $this->isQuanLy();
    }
    public function getRoleAtribute(): array
    {
        return [$this->chuc_vu];
    }
    public function scopeActive($query)
    {
        return $query->where('trang_thai', true);
    }
    public function scopeByRole($query, $role)
    {
        return $query->where('chuc_vu', $role);
    }
}
