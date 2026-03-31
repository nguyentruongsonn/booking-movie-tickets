<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SeatStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $suat_chieu_id;
    public $ghe_id;
    public $status;

    public function __construct($suat_chieu_id, $ghe_id, $status)
    {
        $this->suat_chieu_id = $suat_chieu_id;
        $this->ghe_id = $ghe_id;
        $this->status = $status;
    }

    public function broadcastOn()
    {
        // Tạo một channel riêng cho từng suất chiếu
        return new Channel('showtime.' . $this->suat_chieu_id);
    }
}