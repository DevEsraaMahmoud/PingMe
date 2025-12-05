import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

// Configure Pusher for Laravel Echo
window.Pusher = Pusher;

// Create Echo instance
// This configuration supports both Laravel Reverb and soketi/Pusher
const scheme = import.meta.env.VITE_PUSHER_SCHEME || import.meta.env.VITE_REVERB_SCHEME || 'http';
const isSecure = scheme === 'https';

// Prioritize Reverb variables for Reverb setup
const key = import.meta.env.VITE_REVERB_APP_KEY || import.meta.env.VITE_PUSHER_APP_KEY;
const wsHost = import.meta.env.VITE_REVERB_HOST || import.meta.env.VITE_PUSHER_HOST || '127.0.0.1';
const wsPort = import.meta.env.VITE_REVERB_PORT || import.meta.env.VITE_PUSHER_PORT || 8080;
const wsScheme = import.meta.env.VITE_REVERB_SCHEME || import.meta.env.VITE_PUSHER_SCHEME || 'http';

const config = {
    broadcaster: 'pusher',
    key: key,
    cluster: import.meta.env.VITE_PUSHER_CLUSTER || 'mt1',
    wsHost: wsHost,
    wsPort: wsPort,
    wssPort: wsPort,
    forceTLS: wsScheme === 'https',
    enabledTransports: ['ws', 'wss'],
    disableStats: true,
    encrypted: wsScheme === 'https',
    authEndpoint: '/broadcasting/auth',
    auth: {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
        },
    },
};

window.Echo = new Echo(config);

// Add connection event listeners for debugging
window.Echo.connector.pusher.connection.bind('connected', () => {
    console.log('✅ Echo connected to WebSocket server');
});

window.Echo.connector.pusher.connection.bind('disconnected', () => {
    console.warn('⚠️ Echo disconnected from WebSocket server');
});

window.Echo.connector.pusher.connection.bind('error', (err) => {
    console.error('❌ Echo connection error:', err);
});

window.Echo.connector.pusher.connection.bind('state_change', (states) => {
    console.log('🔄 Echo connection state changed:', states.previous, '->', states.current);
});

// Log when subscribed to a channel
const originalSubscribe = window.Echo.private.bind(window.Echo);
window.Echo.private = function(channelName) {
    console.log('📡 Subscribing to private channel:', channelName);
    const channel = originalSubscribe(channelName);
    
    channel.subscribed(() => {
        console.log('✅ Successfully subscribed to:', channelName);
    });
    
    channel.error((error) => {
        console.error('❌ Channel subscription error:', error);
    });
    
    return channel;
};

console.log('🔧 Echo initialized with config:', {
    key: config.key,
    wsHost: config.wsHost,
    wsPort: config.wsPort,
    scheme: scheme,
});

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

