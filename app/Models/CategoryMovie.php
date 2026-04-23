<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Pivot model cho bảng categories_movies */
class CategoryMovie extends Model
{
    protected $table = 'categories_movies';

    protected $fillable = ['movie_id', 'category_id'];

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
