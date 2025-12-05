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
        $user = auth()->user();
        $conversations = $user
            ->conversations()
            ->with(['latestMessage.user', 'users'])
            ->withCount('messages')
            ->orderByDesc('updated_at')
            ->get()
            ->map(function ($conversation) use ($user) {
                $pivot = $conversation->pivot;
                $lastReadMessageId = $pivot->last_read_message_id ?? 0;
                
                // Count unread messages
                $unreadCount = $conversation->messages()
                    ->where('id', '>', $lastReadMessageId)
                    ->where('user_id', '!=', $user->id)
                    ->count();
                
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
                        'created_at' => $conversation->latestMessage->created_at->toIso8601String(),
                    ] : null,
                    'participants' => $conversation->users->map(fn ($u) => [
                        'id' => $u->id,
                        'name' => $u->name,
                        'email' => $u->email,
                        'last_active_at' => $u->last_active_at?->toIso8601String(),
                    ]),
                    'messages_count' => $conversation->messages_count,
                    'unread_count' => $unreadCount,
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

        return Inertia::render('Dashboard', [
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
        // For original participants (creator + initial users), set joined_at to conversation created_at
        // This way they see all messages from the start
        $conversationCreatedAt = $conversation->created_at;
        $conversation->users()->attach(
            collect($userIds)->mapWithKeys(fn ($id) => [
                $id => ['joined_at' => $conversationCreatedAt]
            ])->toArray()
        );

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
                        'last_active_at' => $u->last_active_at?->toIso8601String(),
                    ]),
                    'messages_count' => $conv->messages_count,
                    'updated_at' => $conv->updated_at,
                ];
            });

        // Get user's participation info
        $userPivot = $conversation->users()->where('users.id', $user->id)->first();
        if (!$userPivot) {
            abort(403, 'You are not a participant in this conversation.');
        }
        
        // Get all messages for the conversation
        // No filtering - all participants see all messages
        $messages = $conversation->messages()
            ->with(['user:id,name,email', 'attachments'])
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($message) => [
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
                'created_at' => $message->created_at,
                'edited_at' => $message->edited_at,
            ]);

        $participants = $conversation->users->map(fn ($u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'last_active_at' => $u->last_active_at?->toIso8601String(),
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

        return Inertia::render('Dashboard', [
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

