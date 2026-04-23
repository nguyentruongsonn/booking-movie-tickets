<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Chuẩn hóa response của Promotion.
 */
class PromotionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'code'            => $this->code,
            'name'            => $this->name,
            'discount_type'   => $this->discount_type,
            'discount_value'  => (float) $this->discount_value,
            'min_order_value' => (float) $this->min_order_value,
            'end_date'        => $this->end_date?->toIso8601String(),
        ];
    }
}
