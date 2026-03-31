<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seats extends Model
{
    protected $fillable = [
        'room_id',
        'seat_type_id',
        'hang_ghe',
        'so_ghe',
        'ma',
        'trang_thai'
    ];


    protected $casts = [
        'trang_thai' => 'boolean',
    ];

    public function room()
    {
        return $this->belongsTo(Rooms::class, 'room_id');
    }

    public function seat_type()
    {
        return $this->belongsTo(seat_type::class, 'seat_type_id');
    }

    public function tickets()
    {
        return $this->hasMany(Tickets::class, 'ghe_id');
    }

}
