<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Seat extends Model
{
    use HasFactory;

    protected $fillable = [
        'screen_id',
        'seat_type_id',
        'row',
        'number',
        'row_index',
        'column_index',
        'label',
        'status',
    ];

    protected $casts = [
        'row_index' => 'integer',
        'column_index' => 'integer',
        'status' => 'integer',
    ];

    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }

    public function seatType()
    {
        return $this->belongsTo(SeatType::class, 'seat_type_id');
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'item_id')->where('item_type', 'ticket');
    }
}
