<?php
namespace App\Events;

use App\Models\Call;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class CallInitiated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Call $call, public string $signal) {}

    public function broadcastOn(): Channel
    {
        return new Channel('conversation.' . $this->call->conversation_id);
    }

    public function broadcastAs(): string { return 'call.signal'; }

    public function broadcastWith(): array
    {
        $this->call->load('initiator:id,name,avatar');
        return [
            'call_uuid'    => $this->call->uuid,
            'call_type'    => $this->call->type,
            'call_status'  => $this->call->status,
            'initiated_by' => $this->call->initiated_by,
            'initiator'    => $this->call->initiator,
            'signal'       => $this->signal, // JSON WebRTC offer/answer/candidate
        ];
    }
}
