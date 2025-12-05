/**
 * Notification Service
 * Handles browser notifications with sound
 */

let notificationPermission = null;
let audioContext = null;

// Request notification permission
export async function requestNotificationPermission() {
    if (!('Notification' in window)) {
        console.warn('This browser does not support notifications');
        return false;
    }

    if (Notification.permission === 'granted') {
        notificationPermission = true;
        return true;
    }

    if (Notification.permission !== 'denied') {
        const permission = await Notification.requestPermission();
        notificationPermission = permission === 'granted';
        return notificationPermission;
    }

    notificationPermission = false;
    return false;
}

// Play notification sound
export function playNotificationSound() {
    try {
        // Create audio context if not exists
        if (!audioContext) {
            audioContext = new (window.AudioContext || window.webkitAudioContext)();
        }

        // Create a simple beep sound
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();

        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);

        oscillator.frequency.value = 800; // Frequency in Hz
        oscillator.type = 'sine';

        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);

        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
        console.error('Error playing notification sound:', error);
    }
}

// Show browser notification
export async function showNotification(title, options = {}) {
    // Request permission if not already granted
    if (notificationPermission === null) {
        await requestNotificationPermission();
    }

    if (notificationPermission && Notification.permission === 'granted') {
        try {
            const notification = new Notification(title, {
                icon: '/favicon.ico',
                badge: '/favicon.ico',
                tag: options.tag || 'message-notification',
                requireInteraction: false,
                ...options,
            });

            // Play sound
            playNotificationSound();

            // Auto-close after 5 seconds
            setTimeout(() => {
                notification.close();
            }, 5000);

            // Handle click
            notification.onclick = () => {
                window.focus();
                notification.close();
                if (options.onClick) {
                    options.onClick();
                }
            };

            return notification;
        } catch (error) {
            console.error('Error showing notification:', error);
        }
    } else {
        // Fallback: just play sound if notifications are not allowed
        playNotificationSound();
    }
}

// Initialize notification service
export function initNotificationService() {
    // Request permission on page load
    requestNotificationPermission();
}

