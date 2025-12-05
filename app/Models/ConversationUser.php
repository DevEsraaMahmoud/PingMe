<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ConversationUser extends Pivot
{
    protected $table = 'conversation_user';

    protected $fillable = [
        'conversation_id',
        'user_id',
        'joined_at',
        'is_muted',
        'last_read_message_id',
        'role',
    ];

    protected function casts(): array
    {
        return [
            'joined_at' => 'datetime',
            'is_muted' => 'boolean',
        ];
    }

    /**
     * Get the conversation.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the last read message.
     */
    public function lastReadMessage(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'last_read_message_id');
    }
}

