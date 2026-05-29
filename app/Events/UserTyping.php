<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTyping implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $conversationId,
        public int    $userId,
        public string $userName,
        public bool   $isTyping
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('conversation.' . $this->conversationId);
    }

    public function broadcastAs(): string { return 'user.typing'; }

    public function broadcastWith(): array
    {
        return [
            'user_id'    => $this->userId,
            'user_name'  => $this->userName,
            'is_typing'  => $this->isTyping,
        ];
    }
}
