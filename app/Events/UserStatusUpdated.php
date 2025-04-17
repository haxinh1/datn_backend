<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class UserStatusUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $user;

    public function __construct(User $user)
    {
        $this->user = $user;
        Log::info('UserStatusUpdated constructed', ['user_id' => $user->id, 'status' => $user->status]);
    }

    public function broadcastOn()
    {
        Log::info('Broadcasting on channel', ['channel' => 'user.' . $this->user->id]);
        return new PrivateChannel('user.' . $this->user->id);
    }

    public function broadcastAs()
    {
        return 'user-status-updated';
    }

    public function broadcastWith()
    {
        $data = [
            'user_id' => $this->user->id,
            'status' => $this->user->status,
            'updated_at' => $this->user->updated_at->toDateTimeString(),
        ];
        Log::info('Broadcasting data', $data);
        return $data;
    }
}