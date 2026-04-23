<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Chuẩn hóa response của Showtime - chỉ trả về fields cần thiết.
 */
class ShowtimeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'scheduled_at' => $this->scheduled_at?->toIso8601String(),
            'start_time'   => $this->scheduled_at?->format('H:i'),
            'date'         => $this->scheduled_at?->format('Y-m-d'),
            'price'        => (float) $this->price,
            'movie'        => $this->whenLoaded('movie', fn () => [
                'id'         => $this->movie->id,
                'title'      => $this->movie->title,
                'slug'       => $this->movie->slug,
                'poster_url' => $this->movie->poster_url,
                'age_rating' => $this->movie->age_rating,
                'duration'   => $this->movie->duration,
            ]),
            'screen'       => $this->whenLoaded('screen', fn () => [
                'id'       => $this->screen->id,
                'name'     => $this->screen->name,
                'capacity' => $this->screen->capacity,
            ]),
        ];
    }
}
