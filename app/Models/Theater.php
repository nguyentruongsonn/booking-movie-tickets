<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Theater extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'address',
        'city',
        'phone',
        'email',
        'status',
    ];

    protected $casts = [
        'status' => 'integer',
    ];

    public function screens()
    {
        return $this->hasMany(Screen::class);
    }
}
