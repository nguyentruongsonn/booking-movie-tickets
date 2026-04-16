<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Products extends Model
{
    protected $fillable = [
        'ten_san_pham',
        'loai_san_pham',
        'gia_ban',
        'so_luong_ton',
        'hinh_anh_url',
        'mo_ta'
    ];
    protected $casts = [
        'gia_ban' => 'decimal:2',
        'so_luong_ton' => 'integer'
    ];
    public function invoiceDetails()
    {
        return $this->hasMany(InvoiceDetails::class, 'san_pham_id');
    }
    public function invoices()
    {
        return $this->belongsToMany(Invoices::class, 'invoice_details', 'san_pham_id', 'hoa_don_id')
                    ->withPivot('ten_san_pham', 'so_luong', 'don_gia', 'thanh_tien')
                    ->withTimestamps();
    }
}
