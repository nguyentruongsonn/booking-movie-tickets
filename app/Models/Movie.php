<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Movie extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'movies';

    const STATUS_INACTIVE = 0;
    const STATUS_SHOWING = 1;
    const STATUS_COMING_SOON = 2;
    const STATUS_FINISHED = 3;

    protected $fillable = [
        'title',
        'slug',
        'original_title',
        'surcharge',
        'description',
        'duration',
        'release_date',
        'end_date',
        'age_rating',
        'status',
        'director',
        'cast',
        'poster_url',
        'trailer_url',
        'backdrops',
        'is_hot',
    ];

    protected $casts = [
        'surcharge' => 'decimal:2',
        'duration' => 'integer',
        'release_date' => 'date',
        'end_date' => 'date',
        'status' => 'integer',
        'backdrops' => 'json',
        'is_hot' => 'boolean',
    ];

    protected $appends = [
        'is_showing',
        'is_coming_soon',
        'dang_chieu',
        'sap_chieu',
    ];


    protected static function boot(): void
    {
        parent::boot();
        static::saving(function (self $movie): void {
            if (empty($movie->slug)) {
                $movie->slug = Str::slug($movie->title);
            }
        });
    }


    public function getThoiLuongFormatAttribute(): string
    {
        $gio = (int) floor($this->duration / 60);
        $phut = $this->duration % 60;
        return $gio > 0 ? "{$gio}h {$phut}p" : "{$phut}p";
    }

    public function getDangChieuAttribute(): bool
    {
        return $this->status === self::STATUS_SHOWING
            && $this->release_date <= today()
            && ($this->end_date === null || $this->end_date >= today());
    }

    public function getSapChieuAttribute(): bool
    {
        return $this->status === self::STATUS_COMING_SOON && $this->release_date > today();
    }

    public function getIsShowingAttribute(): bool
    {
        return $this->status === self::STATUS_SHOWING;
    }

    public function getIsComingSoonAttribute(): bool
    {
        return $this->status === self::STATUS_COMING_SOON;
    }


    public function scopeDangChieu($query)
    {
        return $query->where('status', self::STATUS_SHOWING);
    }


    public function scopeSapChieu($query)
    {
        return $query->where('status', self::STATUS_COMING_SOON)
            ->orderBy('release_date');
    }


    public function scopeIsShowing($query)
    {
        return $query->where('status', self::STATUS_SHOWING);
    }


    public function scopeIsComingSoon($query)
    {
        return $query->where('status', self::STATUS_COMING_SOON)
            ->orderBy('release_date');
    }



    public function showtimes()
    {
        return $this->hasMany(Showtime::class, 'movie_id');
    }

    public function categories()
    {
        return $this->belongsToMany(Category::class, 'categories_movies', 'movie_id', 'category_id')
            ->withTimestamps();
    }
}
