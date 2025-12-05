<?php

namespace App\Http\Controllers;

use App\Events\MessageSent;
use App\Events\NotificationSent;
use App\Models\Conversation;
use App\Models\Message;
use App\Notifications\MessageNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class MessageController extends Controller
{
    /**
     * Store a newly created message.
     */
    public function store(Request $request, Conversation $conversation)
    {
        // Ensure user is a participant
        $user = auth()->user();
        if (!$conversation->users->contains($user)) {
            return response()->json(['error' => 'You are not a participant in this conversation.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'required_without:file|string|max:5000',
            'type' => 'sometimes|in:text,image,attachment,system',
            'file' => 'sometimes|file|max:10240', // 10MB max
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'type' => $request->input('type', 'text'),
            'metadata' => $request->input('metadata'),
        ]);

        // Handle file upload if present
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('attachments', 'public');
            
            $message->attachments()->create([
                'path' => $path,
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
            ]);

            // Update message body if it's an image/attachment type
            if (!$message->body && in_array($message->type, ['image', 'attachment'])) {
                $message->update(['body' => $file->getClientOriginalName()]);
            }
        }

        // Load relationships for broadcasting
        $message->load('user:id,name,email');

        // Broadcast the message
        try {
            broadcast(new MessageSent($message))->toOthers();
            \Log::info('Message broadcasted', [
                'message_id' => $message->id,
                'conversation_id' => $conversation->id,
                'user_id' => $user->id,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to broadcast message', [
                'error' => $e->getMessage(),
                'message_id' => $message->id,
            ]);
        }

        // Send notifications to all participants except the sender
        $participants = $conversation->users()->where('users.id', '!=', $user->id)->get();
        foreach ($participants as $participant) {
            try {
                // Send notification (queued)
                $participant->notify(new MessageNotification($message));
            } catch (\Exception $e) {
                \Log::error('Failed to send notification', [
                    'error' => $e->getMessage(),
                    'user_id' => $participant->id,
                    'message_id' => $message->id,
                ]);
            }
        }

        $messageData = [
            'id' => $message->id,
            'body' => $message->body,
            'type' => $message->type,
            'metadata' => $message->metadata,
            'user' => [
                'id' => $message->user->id,
                'name' => $message->user->name,
                'email' => $message->user->email,
            ],
            'created_at' => $message->created_at->toIso8601String(),
            'edited_at' => $message->edited_at?->toIso8601String(),
        ];

        return response()->json([
            'message' => $messageData,
        ], 201);
    }
}

