<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Authentication Routes
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
Route::post('/register', [RegisterController::class, 'register']);

// Redirect root to conversations (or login if not authenticated)
Route::get('/', function () {
    if (Auth::check()) {
        return redirect('/conversations');
    }
    return redirect('/login');
});

// Protected Routes
Route::middleware('auth')->group(function () {
    Route::get('/conversations', [ConversationController::class, 'index'])->name('conversations.index');
    Route::post('/conversations', [ConversationController::class, 'store'])->name('conversations.store');
    Route::get('/conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
    Route::post('/conversations/{conversation}/messages', [MessageController::class, 'store'])->name('messages.store');
    
    // Notification routes
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-read');
    
    // Test broadcast route (for debugging)
    Route::get('/test-broadcast/{conversation}', function (\App\Models\Conversation $conversation) {
        $message = $conversation->messages()->latest()->first();
        if ($message) {
            broadcast(new \App\Events\MessageSent($message))->toOthers();
            return response()->json(['status' => 'Broadcast sent', 'message_id' => $message->id]);
        }
        return response()->json(['error' => 'No messages found'], 404);
    })->name('test.broadcast');
});
