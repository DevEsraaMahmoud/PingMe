<template>
    <div class="relative notification-dropdown">
        <!-- Bell Icon Button -->
        <button
            @click="toggleDropdown"
            class="notification-button relative p-2 text-gray-700 hover:bg-gray-100 rounded-lg transition-all"
            :class="{ 'bg-gray-100': showDropdown }"
        >
            <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg"
            >
                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                />
            </svg>
            <!-- Badge for unread count -->
            <span
                v-if="unreadCount > 0"
                class="absolute -top-1 -right-1 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center border-2 border-white shadow-sm"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <Transition
            enter-active-class="transition ease-out duration-100"
            enter-from-class="transform opacity-0 scale-95"
            enter-to-class="transform opacity-100 scale-100"
            leave-active-class="transition ease-in duration-75"
            leave-from-class="transform opacity-100 scale-100"
            leave-to-class="transform opacity-0 scale-95"
        >
            <div
                v-if="showDropdown"
                class="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-xl z-[9999] border border-gray-200 dark:border-gray-700 max-h-96 overflow-hidden flex flex-col transition-colors duration-200"
            >
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 dark:border-gray-700 flex items-center justify-between bg-gradient-to-r from-blue-600 to-blue-700 dark:from-blue-700 dark:to-blue-800 text-white transition-colors duration-200">
                <h3 class="font-semibold">Notifications</h3>
                <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    class="text-sm hover:underline"
                >
                    Mark all as read
                </button>
            </div>

            <!-- Notifications List -->
            <div class="overflow-y-auto flex-1">
                <div v-if="notifications.length === 0" class="p-8 text-center text-gray-500 dark:text-gray-400">
                    <svg
                        class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
                        />
                    </svg>
                    <p class="text-sm">No notifications</p>
                </div>

                <div
                    v-for="notification in notifications"
                    :key="notification.id"
                    :class="[
                        'p-4 border-b border-gray-100 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group',
                        !notification.read_at ? 'bg-blue-50 dark:bg-blue-900/30' : '',
                    ]"
                >
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-sm font-semibold">
                                {{ getInitials(notification.data?.user_name || 'U') }}
                            </div>
                        </div>
                        <div 
                            class="flex-1 min-w-0 cursor-pointer"
                            @click="handleNotificationClick(notification)"
                        >
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white">
                                        {{ notification.data?.user_name || 'Someone' }}
                                        <span class="text-xs text-gray-500 dark:text-gray-400 font-normal">
                                            sent a message
                                        </span>
                                    </p>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1 line-clamp-2">
                                        {{ notification.data?.body || 'New message' }}
                                    </p>
                                    <div class="flex items-center justify-between mt-2">
                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                            {{ formatTime(notification.created_at) }}
                                        </p>
                                        <span 
                                            v-if="notification.data?.conversation_title"
                                            class="text-xs text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"
                                        >
                                            {{ notification.data.conversation_title }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-2 ml-2">
                                    <div v-if="!notification.read_at" class="flex-shrink-0">
                                        <div class="w-2 h-2 bg-blue-500 dark:bg-blue-400 rounded-full"></div>
                                    </div>
                                    <button
                                        v-if="!notification.read_at"
                                        @click.stop="markAsRead(notification)"
                                        class="opacity-0 group-hover:opacity-100 p-1 text-gray-400 dark:text-gray-500 hover:text-blue-600 dark:hover:text-blue-400 transition-all"
                                        title="Mark as read"
                                    >
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="notifications.length > 0" class="p-2 border-t border-gray-200 dark:border-gray-700 text-center">
                <button
                    @click="viewAllNotifications"
                    class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-700 dark:hover:text-blue-300 font-medium transition-colors duration-150"
                >
                    View all notifications
                </button>
            </div>
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    notifications: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['notification-read', 'refresh']);

const showDropdown = ref(false);

const unreadCount = computed(() => {
    return props.notifications.filter((n) => !n.read_at).length;
});

const toggleDropdown = (event) => {
    event.stopPropagation();
    showDropdown.value = !showDropdown.value;
};

const closeDropdown = () => {
    showDropdown.value = false;
};

// Handle click outside
let handleClickOutside = null;

onMounted(() => {
    handleClickOutside = (event) => {
        const dropdown = event.target.closest('.notification-dropdown');
        const button = event.target.closest('.notification-button');
        if (!dropdown && !button && showDropdown.value) {
            closeDropdown();
        }
    };
    document.addEventListener('click', handleClickOutside);
});

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    const now = new Date();
    const diffMs = now - date;
    const diffMins = Math.floor(diffMs / 60000);
    const diffHours = Math.floor(diffMs / 3600000);
    const diffDays = Math.floor(diffMs / 86400000);

    if (diffMins < 1) return 'Just now';
    if (diffMins < 60) return `${diffMins}m ago`;
    if (diffHours < 24) return `${diffHours}h ago`;
    if (diffDays < 7) return `${diffDays}d ago`;
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: diffDays >= 365 ? 'numeric' : undefined,
        timeZone: 'Africa/Cairo',
    });
};

const handleNotificationClick = async (notification) => {
    // Navigate to conversation
    const conversationId = notification.data?.conversation_id;
    if (conversationId) {
        // Mark as read if not already read
        if (!notification.read_at) {
            await markAsRead(notification);
        }
        closeDropdown();
        router.visit(`/conversations/${conversationId}`);
    }
};

const markAsRead = async (notification) => {
    if (notification.read_at) return; // Already read
    
    try {
        await axios.post(`/notifications/${notification.id}/read`);
        notification.read_at = new Date().toISOString();
        emit('notification-read', notification.id);
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/mark-all-read');
        // Update local notifications
        props.notifications.forEach(notification => {
            if (!notification.read_at) {
                notification.read_at = new Date().toISOString();
            }
        });
        emit('refresh');
    } catch (error) {
        console.error('Error marking all as read:', error);
    }
};

const viewAllNotifications = () => {
    // Could navigate to a full notifications page
    closeDropdown();
    console.log('View all notifications');
};

onUnmounted(() => {
    if (handleClickOutside) {
        document.removeEventListener('click', handleClickOutside);
    }
});
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>

