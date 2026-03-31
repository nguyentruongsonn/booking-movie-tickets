<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Showtimes extends Model
{
    protected $fillable =[
        'movie_id',
        'room_id',
        'format_id',
        'sound_id',
        'subtitle_id',
        'ngay_gio_chieu',
        'gia_goc',
        'gia_ban',
        'trang_thai'
    ];
    protected $appends = ['gio_chieu', 'ngay_chieu'];
    
    protected $casts = [
        'gia_goc' => 'decimal:2',
        'gia_ban' => 'decimal:2',

        'ngay_gio_chieu' => 'datetime',
    ];
    public function movie()
    {
        return $this->belongsTo(Movies::class, 'movie_id');
    }
    public function room()
    {
        return $this->belongsTo(Rooms::class,'room_id');
    }
    public function format()
    {
        return $this->belongsTo(Format::class,'format_id');
    }
    public function sound()
    {
        return $this->belongsTo(Sound::class,'sound_id');
    }
    public function subtitle()
    {
        return $this->belongsTo(Subtitles::class,'subtitle_id');
    }
    public function tickets()
    {
        return $this->hasMany(Tickets::class, 'lich_chieu_id');
    }

    public function getSoGheConTrongAttribute()
    {
        $tongGhe = $this->tickets()->where('trang_thai','!=','da_huy')->count();
        return $this->room->suc_chua - $tongGhe;
    }

    public function getDaHetVeAttribute()
    {
        return $this->so_ghe_con_trong <= 0;
    }

    public function getCoTheDatVeAttribute()
    {
        return $this->trang_thai === 'con_ve';
    }

    public function getGioChieuAttribute()
    {
        return $this->ngay_gio_chieu ? $this->ngay_gio_chieu->format('H:i') : null;
    }

    public function getNgayChieuAttribute()
    {
        return $this->ngay_gio_chieu ? $this->ngay_gio_chieu->format('d/m/Y') : null;
    }
}
