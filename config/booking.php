<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Loyalty & Points System
    |--------------------------------------------------------------------------
    |
    | The rate at which customers earn loyalty points based on booking amount.
    | 0.05 means 5% of the total order value will be given as points.
    | 1 point = 1 VND when redeemed.
    |
    */
    'loyalty_rate' => env('BOOKING_LOYALTY_RATE', 0.05),

    /*
    |--------------------------------------------------------------------------
    | Seat Holding
    |--------------------------------------------------------------------------
    |
    | Time to live (TTL) for a seat hold in minutes. During this time, other
    | users cannot book the same seat.
    |
    */
    'hold_ttl_minutes' => env('BOOKING_HOLD_TTL_MINUTES', 10),

    /*
    |--------------------------------------------------------------------------
    | Validation Constraints
    |--------------------------------------------------------------------------
    |
    | Maximum number of seats a customer can book in a single order.
    |
    */
    'max_seats_per_order' => env('BOOKING_MAX_SEATS', 10),

];
