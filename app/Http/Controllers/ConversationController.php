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

        // Get all users for creating new conversations
        $allUsers = \App\Models\User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'email')
            ->get();

        // Get recent notifications
        $notifications = auth()->user()
            ->notifications()
            ->where('type', \App\Notifications\MessageNotification::class)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
            ]);

        return Inertia::render('ChatPage', [
            'conversations' => $conversations,
            'currentUserId' => auth()->id(),
            'availableUsers' => $allUsers,
            'notifications' => $notifications,
        ]);
    }

    /**
     * Store a newly created conversation.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['exists:users,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'is_group' => ['boolean'],
        ]);

        $user = auth()->user();
        $userIds = $validated['user_ids'];
        
        // Add current user to participants
        $userIds[] = $user->id;
        $userIds = array_unique($userIds);

        // Check if a 1-on-1 conversation already exists (if not a group)
        if (!($validated['is_group'] ?? false) && count($userIds) === 2) {
            $existingConversation = Conversation::where('is_group', false)
                ->whereHas('users', function ($query) use ($userIds) {
                    $query->whereIn('users.id', $userIds);
                }, '=', count($userIds))
                ->get()
                ->filter(function ($conv) use ($userIds) {
                    return $conv->users->pluck('id')->sort()->values()->toArray() === 
                           collect($userIds)->sort()->values()->toArray();
                })
                ->first();

            if ($existingConversation) {
                return redirect()->route('conversations.show', $existingConversation);
            }
        }

        $conversation = Conversation::create([
            'title' => $validated['title'] ?? null,
            'is_group' => $validated['is_group'] ?? (count($userIds) > 2),
            'created_by' => $user->id,
        ]);

        // Attach users to conversation
        $conversation->users()->attach($userIds);

        return redirect()->route('conversations.show', $conversation);
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

        // Get all users for creating new conversations
        $allUsers = \App\Models\User::where('id', '!=', auth()->id())
            ->select('id', 'name', 'email')
            ->get();

        // Get recent notifications
        $notifications = auth()->user()
            ->notifications()
            ->where('type', \App\Notifications\MessageNotification::class)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
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
            'availableUsers' => $allUsers,
            'notifications' => $notifications,
        ]);
    }
}

