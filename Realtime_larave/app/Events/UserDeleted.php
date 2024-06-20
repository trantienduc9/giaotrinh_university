<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use App\Models\User;

class UserDeleted implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;
    public $user;
    public function __construct(User $user)
    {
        $this->user = $user;
    }
    public function broadcastOn()
    {
        // \Log::debug("UserDeleted {$this->user->name}");
        return new Channel('users');
    }
}