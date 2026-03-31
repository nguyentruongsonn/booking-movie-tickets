<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class seat_type extends Model
{
    //
    protected $fillable = [
        'ten',
        'ma',
        'he_so_gia'
    ];
    protected $casts = [
        'he_so_gia' => 'float',
    ];

    public function seat()
    {
        return $this->hasMany(Seats::class, 'loai_ghe_id');
    }
}
