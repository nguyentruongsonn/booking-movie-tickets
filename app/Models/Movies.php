<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Movies extends Model
{
    //
    protected $fillable = [
        'ten_phim',
        'slug',
        'ten_goc',
        'mo_ta',
        'thoi_luong',
        'ngay_khoi_chieu',
        'ngay_ket_thuc',
        'do_tuoi',
        'trang_thai',
        'dao_dien',
        'dien_vien',
        'poster_url',
        'trailer_url'
    ];
    protected $casts = [
        'thoi_luong' => 'integer',
        'ngay_khoi_chieu' => 'date',
        'ngay_ket_thuc' => 'date',

    ];

    protected static function boot()
    {
        parent::boot(); //gọi boot của lớp cha (model) để laravel khởi tạo các tính năng mặc định
        static::creating(function ($movie) { // gọi method của class hiện tại
            if (empty($movie->slug)) {
                $movie->slug = Str::slug($movie->ten_phim); //hàm biến tiếng Việt có dấu thành chuỗi không dấu có gạch ngang
            }
        });
    }

    public function showtimes(): HasMany
    {
        return $this->hasMany(Showtimes::class , 'movie_id');
    }

    //Quan hệ nhiều nhiều 
    public function categories()
    {
        return $this->belongsToMany(Categories::class , 'categories_movies', 'movie_id', 'category_id')->withTimestamps();
    }

    public function scopeDangChieu()
    {
        return $this->where('trang_thai', true)
            ->where('ngay_khoi_chieu', '<=', today())
            ->where(function ($query) {
            $query->where('ngay_ket_thuc', '>=', today())
                ->orWhereNull('ngay_ket_thuc');
        });
    }

    public function scopeSapChieu()
    {
        return $this->where('trang_thai', true)
            ->where('ngay_khoi_chieu', '>', today())
            ->orderBy('ngay_khoi_chieu', 'asc');
    }
    public function getThoiLuong()
    {
        $gio = floor($this->thoi_luong / 60);
        $phut = $this->thoi_luong % 60;
        if ($gio > 0) {
            return "{$gio}h {$phut}p";
        }
        return "{$phut}p";
    }

    public function getDangChieuAttribute()
    {
        return $this->trang_thai && $this->ngay_khoi_chieu <= today() && ($this->ngay_ket_thuc > today() || $this->ngay_ket_thuc === null);
    }

    public function getSapChieuAttribute()
    {
        return $this->trang_thai && $this->ngay_khoi_chieu > today();
    }

}
