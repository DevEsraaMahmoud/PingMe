<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    /**
     * Get user notifications.
     */
    public function index()
    {
        $notifications = Auth::user()
            ->notifications()
            ->where('type', \App\Notifications\MessageNotification::class)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get()
            ->map(fn ($notification) => [
                'id' => $notification->id,
                'data' => $notification->data,
                'read_at' => $notification->read_at?->toIso8601String(),
                'created_at' => $notification->created_at->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $notifications,
            'unread_count' => Auth::user()->unreadNotifications()->count(),
        ]);
    }

    /**
     * Mark a notification as read.
     */
    public function markAsRead(string $id)
    {
        $notification = Auth::user()->notifications()->find($id);
        
        if ($notification && !$notification->read_at) {
            $notification->markAsRead();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead()
    {
        Auth::user()->unreadNotifications->markAsRead();

        return response()->json(['success' => true]);
    }
}
