<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rooms extends Model
{
    protected $fillable = [
        'ten_phong',
        'ma',
        'loai_phong',
        'suc_chua',
        'trang_thai'
    ];
    protected $casts = [
        'suc_chua' => 'integer',
        'trang_thai' => 'boolean',
    ];

    public function seats()
    {
        return $this->hasMany(Seats::class, 'room_id');
    }

    public function showtimes()
    {
        return $this->hasMany(Showtimes::class, 'phong_id');
    }
}
