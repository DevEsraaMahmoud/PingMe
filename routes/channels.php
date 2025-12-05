<?php

use Illuminate\Support\Facades\Broadcast;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/

Broadcast::channel('conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is a participant in the conversation
    return $user->conversations()->where('conversations.id', $conversationId)->exists();
});

Broadcast::channel('user.{userId}', function ($user, $userId) {
    // Users can only listen to their own notification channel
    return (int) $user->id === (int) $userId;
});

// Presence channel for online status
Broadcast::channel('presence-conversation.{conversationId}', function ($user, $conversationId) {
    // Check if user is a participant
    if ($user->conversations()->where('conversations.id', $conversationId)->exists()) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
    return false;
});

