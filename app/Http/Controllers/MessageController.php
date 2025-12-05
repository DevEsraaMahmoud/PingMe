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
        // Ensure user is a participant and hasn't left
        $user = auth()->user();
        $userPivot = $conversation->users()->where('users.id', $user->id)->first();
        
        if (!$userPivot || $userPivot->pivot->left_at) {
            return response()->json(['error' => 'You are not a participant in this conversation.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'body' => 'nullable|string|max:5000',
            'type' => 'sometimes|in:text,image,attachment,system',
            'files' => 'sometimes|array|max:5',
            'files.*' => 'image|mimes:jpeg,png,webp|max:5120', // 5MB max per image
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $messageType = $request->input('type', 'text');
        $hasFiles = $request->hasFile('files') && count($request->file('files')) > 0;
        
        if ($hasFiles) {
            $messageType = 'image';
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'user_id' => $user->id,
            'body' => $request->input('body'),
            'type' => $messageType,
            'metadata' => $request->input('metadata'),
        ]);

        // Handle image uploads
        $attachments = [];
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $path = $file->store('attachments', 'public');
                $attachment = $message->attachments()->create([
                    'path' => $path,
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                ]);
                $attachments[] = $attachment;
            }
        }

        // Load relationships for broadcasting
        $message->load(['user:id,name,email', 'attachments']);

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

        // Send notifications to all active participants except the sender
        $participants = $conversation->users()
            ->where('users.id', '!=', $user->id)
            ->wherePivotNull('left_at')
            ->get();
            
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
            'attachments' => $message->attachments->map(fn ($att) => [
                'id' => $att->id,
                'url' => asset('storage/' . $att->path),
                'mime_type' => $att->mime_type,
                'size' => $att->size,
            ]),
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

