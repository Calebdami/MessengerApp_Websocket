<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserStatusChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int    $userId,
        public string $status,
        public ?string $lastSeenAt
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('presence');
    }

    public function broadcastAs(): string { return 'user.status'; }

    public function broadcastWith(): array
    {
        return [
            'user_id'      => $this->userId,
            'status'       => $this->status,
            'last_seen_at' => $this->lastSeenAt,
        ];
    }
}
