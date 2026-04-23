<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Showtime extends Model
{
    use HasFactory, SoftDeletes;

    const STATUS_CANCELLED = 0;
    const STATUS_AVAILABLE = 1;
    const STATUS_SOLD_OUT = 2;
    const STATUS_FINISHED = 3;

    protected $fillable = [
        'movie_id',
        'screen_id',
        'format_id',
        'sound_id',
        'subtitle_id',
        'scheduled_at',
        'price',
        'status',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'price' => 'decimal:2',
        'status' => 'integer',
    ];

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getTimeAttribute(): ?string
    {
        return $this->scheduled_at?->format('H:i');
    }

    public function getDateAttribute(): ?string
    {
        return $this->scheduled_at?->format('d/m/Y');
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function screen()
    {
        return $this->belongsTo(Screen::class);
    }

    public function format()
    {
        return $this->belongsTo(Format::class);
    }

    public function sound()
    {
        return $this->belongsTo(Sound::class);
    }

    public function subtitle()
    {
        return $this->belongsTo(Subtitles::class, 'subtitle_id');
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class, 'item_id')->where('item_type', 'ticket');
    }
}
