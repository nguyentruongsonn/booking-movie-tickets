<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'item_type', // 'ticket', 'product', 'combo'
        'item_id',
        'quantity',
        'unit_price',
        'total_price',
        'metadata',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'total_price' => 'decimal:2',
        'quantity' => 'integer',
        'metadata' => 'json',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function item()
    {
        // Polymorphic or manual lookup based on item_type
        if ($this->item_type === 'ticket') {
            return $this->belongsTo(Seat::class, 'item_id');
        } elseif ($this->item_type === 'product') {
            return $this->belongsTo(Product::class, 'item_id');
        }
        return null;
    }
}
