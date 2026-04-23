<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';

    protected $fillable = ['ten_loai', 'mo_ta'];

    public function movies()
    {
        return $this->belongsToMany(Movie::class, 'categories_movies', 'category_id', 'movie_id')
                    ->withTimestamps();
    }
}
