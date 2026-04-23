<?php

namespace App\Http\Resources;

use App\Models\InvoiceDetail;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;


class OrderSummaryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                 => $this->id,
            'order_number'       => $this->code,
            'gateway_order_code' => $this->gateway_order_code,
            'status'             => $this->status,
            'total_amount'       => (float) $this->total_amount,
            'payment_status'     => $this->payment_status,
            'checkout_url'       => $this->checkout_url,
            'showtime'           => [
                'id'           => $this->showtime_id,
                'scheduled_at' => optional($this->showtime)->scheduled_at?->toIso8601String(),
                'movie' => [
                    'id'         => $this->showtime?->movie?->id,
                    'title'      => $this->showtime?->movie?->title,
                    'slug'       => $this->showtime?->movie?->slug,
                    'poster_url' => $this->showtime?->movie?->poster_url,
                ],
                'screen' => [
                    'id'   => $this->showtime?->screen?->id,
                    'name' => $this->showtime?->screen?->name,
                ],
            ],
            'tickets' => $this->orderItems->where('item_type', 'ticket')->map(fn ($item) => [
                'id'         => $item->id,
                'seat_id'    => $item->item_id,
                'name'       => $item->metadata['seat_label'] ?? 'Ticket',
                'unit_price' => (float) $item->unit_price,
            ])->values(),
            'products' => $this->orderItems->where('item_type', 'product')->map(fn ($item) => [
                'id'         => $item->id,
                'product_id' => $item->item_id,
                'name'       => $item->metadata['product_name'] ?? 'Product',
                'quantity'   => (int) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'total_price' => (float) $item->total_price,
            ])->values(),
            'discounts' => [
                'voucher_discount' => (float) ($this->payload['voucher_discount'] ?? 0),
                'point_discount'   => (float) ($this->payload['point_discount'] ?? 0),
                'total_discount'   => (float) ($this->payload['discount_amount'] ?? 0),
            ],
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
