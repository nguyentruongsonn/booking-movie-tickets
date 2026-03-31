<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
class Categories extends Model
{
    //
    protected $filable = [
        'ten',
        'slug',
        'mo_ta'
    ];
   
    protected static function boot()
    {
        parent::boot();
        static::creating(function($category)
        {
            if(empty($category->slug))
            {
                $category->slug = Str::slug($category->ten);
            }
        });
    }

    public function movies()
    {
        return $this->belongsToMany(Movies::class,'categories_movies','category_id','movie_id')->withTimestamps();
    }
}
