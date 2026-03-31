<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Format extends Model
{
    //

    protected $fillable = [
        'ten',
        'ma',
        'gia'
    ];
    protected $casts = [
        'gia' => 'float',
    ];




}
