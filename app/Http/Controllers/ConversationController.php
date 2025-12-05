<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ConversationController extends Controller
{
    /**
     * Display a listing of conversations.
     */
    public function index(): Response
    {
        $conversations = auth()->user()
            ->conversations()
            ->with(['latestMessage.user', 'users'])
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conversation) {
                return [
                    'id' => $conversation->id,
                    'title' => $conversation->title,
                    'is_group' => $conversation->is_group,
                    'latest_message' => $conversation->latestMessage ? [
                        'id' => $conversation->latestMessage->id,
                        'body' => $conversation->latestMessage->body,
                        'user' => [
                            'id' => $conversation->latestMessage->user->id,
                            'name' => $conversation->latestMessage->user->name,
                        ],
                        'created_at' => $conversation->latestMessage->created_at,
                    ] : null,
                    'participants' => $conversation->users->map(fn ($user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]),
                    'messages_count' => $conversation->messages_count,
                    'updated_at' => $conversation->updated_at,
                ];
            });

        return Inertia::render('ChatPage', [
            'conversations' => $conversations,
            'currentUserId' => auth()->id(),
        ]);
    }

    /**
     * Display the specified conversation.
     */
    public function show(Conversation $conversation): Response
    {
        // Ensure user is a participant
        $user = auth()->user();
        if (!$conversation->users->contains($user)) {
            abort(403, 'You are not a participant in this conversation.');
        }

        // Load conversations for sidebar
        $conversations = $user
            ->conversations()
            ->with(['latestMessage.user', 'users'])
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conv) {
                return [
                    'id' => $conv->id,
                    'title' => $conv->title,
                    'is_group' => $conv->is_group,
                    'latest_message' => $conv->latestMessage ? [
                        'id' => $conv->latestMessage->id,
                        'body' => $conv->latestMessage->body,
                        'user' => [
                            'id' => $conv->latestMessage->user->id,
                            'name' => $conv->latestMessage->user->name,
                        ],
                        'created_at' => $conv->latestMessage->created_at,
                    ] : null,
                    'participants' => $conv->users->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                    ]),
                    'messages_count' => $conv->messages_count,
                    'updated_at' => $conv->updated_at,
                ];
            });

        $messages = $conversation->messages()
            ->with('user:id,name,email')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($message) => [
                'id' => $message->id,
                'body' => $message->body,
                'type' => $message->type,
                'metadata' => $message->metadata,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                    'email' => $message->user->email,
                ],
                'created_at' => $message->created_at,
                'edited_at' => $message->edited_at,
            ]);

        $participants = $conversation->users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
        ]);

        return Inertia::render('ChatPage', [
            'conversations' => $conversations,
            'conversation' => [
                'id' => $conversation->id,
                'title' => $conversation->title,
                'is_group' => $conversation->is_group,
                'participants' => $participants,
            ],
            'messages' => $messages,
            'currentUserId' => auth()->id(),
        ]);
    }
}

