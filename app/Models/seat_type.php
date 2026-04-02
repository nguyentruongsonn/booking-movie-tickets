<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class seat_type extends Model
{
    protected $fillable = [
        'ten',
        'ma',
        'them_gia'
    ];

    protected $casts = [
        'them_gia' => 'float',
    ];

    public function seat()
    {
        return $this->hasMany(Seats::class, 'seat_type_id');
    }
}
