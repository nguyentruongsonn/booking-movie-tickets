<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class categories_movies extends Model
{
    protected $fillable = [
        'category_id',
        'movie_id'
    ];

    public function movie()
    {
        return $this->belongsTo(Movies::class);
    }

    public function category()
    {
        return $this->belongsTo(Categories::class);
    }
}
