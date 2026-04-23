<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    protected $table = 'promotions';

    protected $fillable = [
        'code',
        'name',
        'category',
        'description',
        'discount_type',
        'discount_value',
        'min_order_value',
        'max_discount_amount',
        'start_date',
        'end_date',
        'usage_limit',
        'usage_count',
        'daily_usage_limit',
        'status',
    ];

    protected $casts = [
        'discount_value'      => 'float',
        'min_order_value'     => 'float',
        'max_discount_amount' => 'float',
        'start_date'          => 'datetime',
        'end_date'            => 'datetime',
        'usage_limit'         => 'integer',
        'usage_count'         => 'integer',
        'daily_usage_limit'   => 'integer',
        'status'              => 'boolean',
    ];

    public function isValid(): bool
    {
        return $this->status
            && now()->between($this->start_date, $this->end_date)
            && (!$this->usage_limit || $this->usage_count < $this->usage_limit);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_promotion')
            ->withPivot('status', 'used_at', 'order_id', 'usage_count')
            ->withTimestamps();
    }
}
