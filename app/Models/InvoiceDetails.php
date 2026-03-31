<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceDetails extends Model
{
    protected $fillable = [
        'hoa_don_id',
        'san_pham_id',
        'so_luong',
        'don_gia',
    ];
    protected $casts = [
        'so_luong' => 'integer',
        'don_gia' => 'decimal:2',
    ];

    public function invoice()
    {
        return $this->belongsTo(Invoices::class, 'hoa_don_id');
    }

    public function product()
    {
        return $this->belongsTo(Products::class, 'san_pham_id');
    }

    public function getThanhTienAttribute()
    {
        return $this->so_luong * $this->don_gia;
    }
}
