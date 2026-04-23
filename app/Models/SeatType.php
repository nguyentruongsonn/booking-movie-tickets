<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeatType extends Model
{
    protected $table = 'seat_types';

    protected $fillable = ['ten', 'ma', 'them_gia'];

    protected $casts = [
        'them_gia' => 'float',
    ];

    public function seats()
    {
        return $this->hasMany(Seat::class, 'seat_type_id');
    }
}
