import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Pusher for Laravel Echo
window.Pusher = Pusher;

// Create Echo instance
// This configuration supports both Laravel Reverb and soketi/Pusher
const config = {
    broadcaster: 'pusher',
    key: import.meta.env.VITE_PUSHER_APP_KEY || import.meta.env.VITE_REVERB_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_CLUSTER || 'mt1',
    wsHost: import.meta.env.VITE_PUSHER_HOST || import.meta.env.VITE_REVERB_HOST || window.location.hostname,
    wsPort: import.meta.env.VITE_PUSHER_PORT || import.meta.env.VITE_REVERB_PORT || 6001,
    wssPort: import.meta.env.VITE_PUSHER_PORT || import.meta.env.VITE_REVERB_PORT || 6001,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME || import.meta.env.VITE_REVERB_SCHEME || 'http') === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    encrypted: true,
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
};

window.Echo = new Echo(config);

/*
 * Configuration Notes:
 *
 * For Laravel Reverb (default recommended):
 * - Set BROADCAST_DRIVER=reverb in .env
 * - VITE_PUSHER_HOST=127.0.0.1 (or your Reverb host)
 * - VITE_PUSHER_PORT=8080 (default Reverb port)
 * - VITE_PUSHER_SCHEME=http (or https in production)
 * - VITE_PUSHER_APP_KEY=your-app-key (from Reverb config)
 *
 * For soketi (Pusher protocol compatible):
 * - Set BROADCAST_DRIVER=pusher in .env
 * - VITE_PUSHER_HOST=127.0.0.1 (or soketi host)
 * - VITE_PUSHER_PORT=6001 (default soketi port)
 * - VITE_PUSHER_SCHEME=http (or https in production)
 * - VITE_PUSHER_APP_KEY=your-app-key (from soketi config)
 *
 * For Pusher.com (cloud service):
 * - Set BROADCAST_DRIVER=pusher in .env
 * - VITE_PUSHER_CLUSTER=your-cluster (e.g., 'mt1', 'eu', etc.)
 * - VITE_PUSHER_APP_KEY=your-pusher-key
 * - Leave VITE_PUSHER_HOST and VITE_PUSHER_PORT empty (uses Pusher cloud)
 */

