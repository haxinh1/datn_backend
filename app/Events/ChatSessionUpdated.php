<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ChatSessionUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $chatSession;

    public function __construct($chatSession)
    {
        $this->chatSession = $chatSession;
    }

    public function broadcastOn()
    {
        Log::info('ChatSessionUpdated', ['chatSession' => $this->chatSession->toArray()]);
        return new PrivateChannel('chat-sessions');
    }

    public function broadcastAs()
    {
        return 'chat-session-updated';
    }

    public function broadcastWith()
    {
        return [
            'chat_session' => $this->chatSession
        ];
    }
}