# PingMe - Real-Time Chat Application

A real-time chat application built with Laravel 12, Vue 3, Inertia.js, and Laravel Reverb.
 📸 Demo

<p align="center">
  <img src="screenshots/2.png" width="300"/>
</p>

<p align="center">
  <img src="screenshots/1.png" width="300"/>
</p>

## Features

- Real-time messaging with Laravel Reverb
- Online/offline presence indicators
- Typing indicators
- Image attachments with preview
- Audio & browser notifications
- Unread message badges
- Dark mode support
- Responsive design with TailwindCSS

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.3+)
- **Frontend**: Vue 3 + Inertia.js + Vite
- **Realtime**: Laravel Reverb
- **Styling**: TailwindCSS

## Quick Start

### 1. Install Dependencies

```bash
composer install
npm install
```

### 2. Setup Environment

```bash
cp .env.example .env
php artisan key:generate
```

Configure your database and Reverb settings in `.env`:

```env
DB_CONNECTION=mysql
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password

BROADCAST_CONNECTION=reverb
REVERB_APP_ID=local-app-id
REVERB_APP_KEY=local-app-key
REVERB_APP_SECRET=local-app-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

### 3. Setup Database

```bash
php artisan migrate
php artisan storage:link
```

### 4. Install Reverb (if not already installed)

```bash
composer require laravel/reverb
php artisan install:broadcasting
```

## Running the Application

Open **3 terminal windows**:

**Terminal 1** - Laravel server:
```bash
php artisan serve
```

**Terminal 2** - Reverb WebSocket server:
```bash
php artisan reverb:start
```

**Terminal 3** - Queue worker (for notifications):
```bash
php artisan queue:work
```

**Terminal 4** (optional) - Vite dev server:
```bash
npm run dev
```

Visit `http://localhost:8000` and register/login to start chatting!

## Testing

1. Register/login as User A in Browser 1
2. Register/login as User B in Browser 2
3. Create a conversation via tinker:
   ```bash
   php artisan tinker
   ```
   ```php
   $user1 = App\Models\User::first();
   $user2 = App\Models\User::skip(1)->first();
   $conv = App\Models\Conversation::create(['created_by' => $user1->id]);
   $conv->users()->attach([$user1->id, $user2->id]);
   ```
4. Open `/conversations/{id}` in both browsers and start chatting!

## Troubleshooting

- **WebSocket not connecting**: Ensure Reverb is running (`php artisan reverb:start`)
- **Notifications not working**: Make sure queue worker is running (`php artisan queue:work`)
- **Images not loading**: Run `php artisan storage:link`
- **Dark mode not working**: Clear browser cache and rebuild assets (`npm run build`)

## License

MIT License
