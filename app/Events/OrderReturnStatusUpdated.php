<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderReturnStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $order;

    /**
     * Create a new event instance.
     *
     * @param Order $order
     * @return void
     */
    public function __construct(Order $order)
    {
        $this->order = $order;
    }

    /**
     * The channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|\Illuminate\Broadcasting\PresenceChannel
     */
    public function broadcastOn()
    {
        return new Channel('order-return-status-channel');  
    }

    /**
     * Tùy chỉnh tên sự kiện.
     *
     * @return string
     */
    public function broadcastAs()
    {
        return 'order-return-status-updated';  
    }

    /**
     * Dữ liệu phát sóng.
     *
     * @return array
     */
    public function broadcastWith()
    {
        return [
            'order_id' => $this->order->id,
            'status_id' => $this->order->status_id,
            'updated_at' => $this->order->updated_at->toDateTimeString(),
            'note' => $this->order->note,  
        ];
    }
}
