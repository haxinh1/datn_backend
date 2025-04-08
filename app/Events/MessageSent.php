<?php

namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    /**
     * Tạo một instance của sự kiện
     */
    public function __construct(Message $message)
    {
        $this->message = $message;
    }

    /**
     * Kênh phát sóng
     */
    public function broadcastOn()
    {
        return new PrivateChannel('chat.' . $this->message->chat_session_id);
    }

    /**
     * Tên sự kiện phát sóng
     */
    public function broadcastAs()
    {
        return 'message.sent';
    }
}
