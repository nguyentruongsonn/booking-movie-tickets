<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Orders extends Model
{
    protected $fillable = [
        'ma_don_hang',
        'customer_id',
        'tong_tien',
        'trang_thai',
    ];

    public function customer()
    {
        return $this->belongsTo(Customers::class, 'customer_id');
    }
    public function tickets()
    {
        return $this->hasMany(Tickets::class, 'ma_don_hang');
    }
}
