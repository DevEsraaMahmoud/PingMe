# PingMe - Real-Time Chat Dashboard

A professional and elegant real-time chat application built with Laravel 12, Vue 3, Inertia.js, and Laravel Reverb.

## Features

- **Real-time messaging** using Laravel Broadcasting with Reverb
- **Professional Dashboard** with three-column layout (Conversations, Chat, Participants)
- **Presence channels** for online/away status
- **Typing indicators** using whisper events
- **Image attachments** with preview and full-size modal view
- **Sound notifications** (ping sound) for incoming messages (recipients only)
- **Browser notifications** with permission handling
- **Unread message badges** in conversation list
- **Search conversations** functionality
- **Queueable notifications** system
- **Optimistic UI** updates
- **Responsive design** with TailwindCSS

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: Vue 3 + Inertia.js + Vite
- **Realtime**: Laravel Broadcasting (Reverb or soketi)
- **Client**: Laravel Echo + pusher-js

## Prerequisites

- PHP 8.3 or higher
- Composer
- Node.js 18+ and npm
- MySQL/PostgreSQL/SQLite database
- WebSocket server (Reverb or soketi)

## Installation

### 1. Clone and Install Dependencies

```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install
```

### 2. Environment Configuration

Copy the `.env.example` file to `.env`:

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Database Setup

Configure your database in `.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

Run migrations:

```bash
php artisan migrate
```

### 4. Broadcasting Configuration

#### Option A: Laravel Reverb (Recommended)

Install Reverb:

```bash
composer require laravel/reverb
php artisan install:broadcasting
```

Configure `.env`:

```env
BROADCAST_CONNECTION=reverb

# Reverb Configuration
REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_SERVER_HOST=0.0.0.0
REVERB_SERVER_PORT=8080
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Vite Configuration (mapped to Reverb)
VITE_PUSHER_APP_KEY="${REVERB_APP_KEY}"
VITE_PUSHER_HOST="${REVERB_HOST}"
VITE_PUSHER_PORT="${REVERB_PORT}"
VITE_PUSHER_SCHEME="${REVERB_SCHEME}"
```

#### Option B: Soketi (Pusher Protocol Compatible)

Install Pusher PHP SDK:

```bash
composer require pusher/pusher-php-server
```

Configure `.env`:

```env
BROADCAST_DRIVER=pusher

PUSHER_APP_ID=your-app-id
PUSHER_APP_KEY=your-key
PUSHER_APP_SECRET=your-secret
PUSHER_HOST=127.0.0.1
PUSHER_PORT=6001
PUSHER_SCHEME=http

VITE_PUSHER_APP_KEY="${PUSHER_APP_KEY}"
VITE_PUSHER_HOST="${PUSHER_HOST}"
VITE_PUSHER_PORT="${PUSHER_PORT}"
VITE_PUSHER_SCHEME="${PUSHER_SCHEME}"
```

See the `docker-compose.yml` file for a quick soketi setup.

### 5. Frontend Dependencies

Install required npm packages:

```bash
npm install vue@3 @inertiajs/inertia @inertiajs/inertia-vue3 laravel-echo pusher-js
```

Optional packages:

```bash
npm install axios dayjs lodash
```

### 6. Build Assets

```bash
npm run dev
# Or for production:
npm run build
```

## Running the Application

### 1. Start Laravel Server

```bash
php artisan serve
```

### 2. Start WebSocket Server

#### For Reverb:

```bash
php artisan reverb:start
```

#### For Soketi (using Docker):

```bash
docker-compose up -d soketi
```

Or run soketi directly:

```bash
npx @soketi/soketi start
```

### 3. Start Queue Worker (for notifications)

Notifications are queueable, so you need to run a queue worker:

```bash
php artisan queue:work
```

Or use the database queue driver (default):

```bash
php artisan queue:work --queue=default
```

### 4. Start Vite Dev Server (if using `npm run dev`)

```bash
npm run dev
```

The application will be available at `http://localhost:8000`.

## Testing Locally

### Manual Test Plan

1. **Create Test Users**:
   - Register/login as User A in Browser 1
   - Register/login as User B in Browser 2

2. **Create a Conversation**:
   - You'll need to create a conversation manually via tinker or a seeder:
   ```php
   php artisan tinker
   ```
   ```php
   $user1 = App\Models\User::first();
   $user2 = App\Models\User::skip(1)->first();
   $conversation = App\Models\Conversation::create([
       'created_by' => $user1->id,
       'is_group' => false,
   ]);
   $conversation->users()->attach([$user1->id, $user2->id]);
   ```

3. **Test Real-Time Messaging**:
   - Open `/conversations/{id}` in both browsers
   - Send a message from Browser 1
   - Verify it appears instantly in Browser 2
   - Send a message from Browser 2
   - Verify it appears instantly in Browser 1

4. **Test Typing Indicators**:
   - Start typing in Browser 1
   - Check Browser 2 console for typing events

### Database Seeders (Optional)

Create a seeder to quickly set up test data:

```bash
php artisan make:seeder ChatSeeder
```

## Troubleshooting

### WebSocket Connection Issues

1. **Check WebSocket Server Status**:
   - Reverb: Ensure `php artisan reverb:start` is running
   - Soketi: Check Docker container or process status

2. **Verify Environment Variables**:
   - Ensure `VITE_PUSHER_*` variables match your WebSocket server config
   - Restart Vite dev server after changing `.env` variables

3. **Check Browser Console**:
   - Look for WebSocket connection errors
   - Verify authentication endpoint is accessible (`/broadcasting/auth`)

4. **CORS Issues**:
   - Ensure `APP_URL` matches your frontend URL
   - Check `config/cors.php` settings

### Broadcasting Not Working

1. **Verify Channel Authorization**:
   - Check `routes/channels.php` for correct authorization logic
   - Ensure user is authenticated

2. **Check Event Broadcasting**:
   - Verify `MessageSent` event implements `ShouldBroadcastNow`
   - Check Laravel logs for broadcasting errors

3. **Queue Configuration**:
   - Notifications are queueable, ensure queue worker is running:
   ```bash
   php artisan queue:work
   ```
   - Check `.env` for `QUEUE_CONNECTION=database` (default)
   - Verify `jobs` table exists (created by default migrations)

### Frontend Issues

1. **Echo Not Initialized**:
   - Check browser console for errors
   - Verify `resources/js/bootstrap/echo.js` is imported in `app.js`
   - Ensure WebSocket server is running

2. **Messages Not Appearing**:
   - Check Vue component event listeners
   - Verify channel subscription in `ChatPage.vue`
   - Check browser network tab for WebSocket messages

## Production Deployment

### SSL/HTTPS Configuration

1. **Update `.env`**:
   ```env
   APP_URL=https://yourdomain.com
   REVERB_SCHEME=https
   # or
   PUSHER_SCHEME=https
   ```

2. **Configure Reverse Proxy** (Nginx example):
   ```nginx
   location /app/ {
       proxy_pass http://127.0.0.1:8080;
       proxy_http_version 1.1;
       proxy_set_header Upgrade $http_upgrade;
       proxy_set_header Connection "Upgrade";
       proxy_set_header Host $host;
   }
   ```

### Authentication

- Ensure proper authentication middleware is applied
- Configure CSRF protection
- Set up proper session configuration for production

### Scaling Considerations

1. **Horizontal Scaling**:
   - Use Redis for shared session storage
   - Configure Redis for broadcasting
   - Use load balancer with sticky sessions

2. **Queue Workers**:
   - Use `ShouldBroadcastNow` for immediate broadcasting (current setup)
   - For better performance, consider using queues:
     ```php
     // Change ShouldBroadcastNow to ShouldBroadcast
     // Run queue workers: php artisan queue:work
     ```

3. **Database Optimization**:
   - Add indexes on frequently queried columns
   - Consider pagination for large message histories
   - Implement message archiving for old conversations

### Environment Variables for Production

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

BROADCAST_DRIVER=reverb
# or pusher for soketi

# Reverb Production Config
REVERB_SCHEME=https
REVERB_HOST=yourdomain.com
REVERB_PORT=443

# Or Soketi Production Config
PUSHER_SCHEME=https
PUSHER_HOST=yourdomain.com
PUSHER_PORT=443
```

## Project Structure

```
app/
├── Events/
│   ├── MessageSent.php          # Broadcasting event
│   └── NotificationSent.php     # Notification broadcast event
├── Http/
│   └── Controllers/
│       ├── ConversationController.php
│       └── MessageController.php
├── Models/
│   ├── Conversation.php
│   ├── ConversationUser.php
│   ├── Message.php
│   └── MessageAttachment.php
└── Notifications/
    └── MessageNotification.php  # Queueable notification

database/
└── migrations/
    ├── xxxx_create_conversations_table.php
    ├── xxxx_create_messages_table.php
    ├── xxxx_create_message_attachments_table.php
    ├── xxxx_create_conversation_user_table.php
    └── xxxx_create_notifications_table.php

resources/
├── js/
│   ├── Pages/
│   │   └── ChatPage.vue
│   ├── Components/
│   │   ├── ChatList.vue
│   │   ├── ChatWindow.vue
│   │   ├── Composer.vue
│   │   ├── MessageBubble.vue
│   │   └── Participants.vue
│   ├── services/
│   │   └── notificationService.js  # Notification service with sound
│   ├── bootstrap/
│   │   └── echo.js              # Echo configuration
│   └── app.js
└── views/
    └── app.blade.php            # Inertia root template

routes/
├── web.php                       # Application routes
└── channels.php                 # Broadcast channel authorization
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).

## Additional Resources

- [Laravel Broadcasting Documentation](https://laravel.com/docs/broadcasting)
- [Laravel Reverb Documentation](https://reverb.laravel.com/docs)
- [Laravel Echo Documentation](https://laravel.com/docs/broadcasting#client-side-installation)
- [Inertia.js Documentation](https://inertiajs.com/)
- [Vue 3 Documentation](https://vuejs.org/)
