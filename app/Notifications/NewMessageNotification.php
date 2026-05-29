<?php
namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\Channel;

class NewMessageNotification extends Notification implements ShouldQueue, ShouldBroadcast
{
    use Queueable;

    public function __construct(public Message $message) {}

    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable): array
    {
        return [
            'type'            => 'new_message',
            'message_id'      => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'sender_name'     => $this->message->sender->name,
            'sender_avatar'   => $this->message->sender->avatar_url,
            'preview'         => $this->message->display_content,
        ];
    }

    public function toBroadcast($notifiable): array
    {
        return $this->toArray($notifiable);
    }

    public function broadcastOn(): Channel
    {
        return new Channel('notifications.' . $this->message->sender_id);
    }
}
