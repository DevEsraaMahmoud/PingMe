<?php

namespace App\Notifications;

use App\Models\Message;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class MessageNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        public Message $message
    ) {
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Get the type of the notification being broadcast.
     */
    public function broadcastType(): string
    {
        return 'message.notification';
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        // Load conversation if not already loaded
        if (!$this->message->relationLoaded('conversation')) {
            $this->message->load('conversation');
        }
        
        return [
            'message_id' => $this->message->id,
            'conversation_id' => $this->message->conversation_id,
            'conversation_title' => $this->message->conversation->title ?? $this->getConversationTitle($this->message->conversation),
            'user_id' => $this->message->user_id,
            'user_name' => $this->message->user->name,
            'body' => $this->message->body,
            'type' => $this->message->type,
            'created_at' => $this->message->created_at->toIso8601String(),
        ];
    }
    
    /**
     * Get conversation title or generate one from participants.
     */
    private function getConversationTitle($conversation): string
    {
        if ($conversation->title) {
            return $conversation->title;
        }
        
        // For 1-on-1 conversations, return the other participant's name
        if (!$conversation->is_group) {
            $otherUser = $conversation->users()->where('users.id', '!=', $this->message->user_id)->first();
            return $otherUser ? $otherUser->name : 'Chat';
        }
        
        return 'Group Chat';
    }

    /**
     * Get the broadcastable representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toBroadcast(object $notifiable): array
    {
        return [
            'id' => $this->id,
            'type' => get_class($this),
            'data' => $this->toArray($notifiable),
            'read_at' => null,
        ];
    }
}
