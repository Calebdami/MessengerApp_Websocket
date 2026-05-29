<?php
namespace App\Events;

use App\Models\Message;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    public function broadcastOn(): Channel
    {
        return new Channel('conversation.' . $this->message->conversation_id);
    }

    public function broadcastAs(): string { return 'message.sent'; }

    public function broadcastWith(): array
    {
        $this->message->load(['sender:id,name,username,avatar,status', 'replyTo.sender:id,name', 'reactions.user:id,name']);
        return [
            'message' => [
                'id'              => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'sender_id'       => $this->message->sender_id,
                'type'            => $this->message->type,
                'content'         => $this->message->content,
                'file_name'       => $this->message->file_name,
                'file_size'       => $this->message->file_size,
                'file_size_human' => $this->message->file_size_human,
                'file_mime'       => $this->message->file_mime,
                'file_url'        => $this->message->file_url,
                'duration'        => $this->message->duration,
                'metadata'        => $this->message->metadata,
                'is_edited'       => $this->message->is_edited,
                'is_deleted'      => $this->message->is_deleted,
                'reply_to'        => $this->message->replyTo,
                'reactions'       => $this->message->reactions,
                'sender'          => $this->message->sender,
                'created_at'      => $this->message->created_at->toISOString(),
            ],
        ];
    }
}
