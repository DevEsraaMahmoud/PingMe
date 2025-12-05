<template>
    <div class="relative">
        <!-- Bell Icon Button -->
        <button
            @click="toggleDropdown"
            class="relative p-2 text-white hover:bg-white hover:bg-opacity-20 rounded-lg transition-all"
            :class="{ 'bg-white bg-opacity-20': showDropdown }"
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
                class="absolute top-0 right-0 bg-red-500 text-white text-xs font-bold rounded-full h-5 w-5 flex items-center justify-center"
            >
                {{ unreadCount > 99 ? '99+' : unreadCount }}
            </span>
        </button>

        <!-- Dropdown -->
        <div
            v-if="showDropdown"
            v-click-outside="closeDropdown"
            class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl z-50 border border-gray-200 max-h-96 overflow-hidden flex flex-col"
        >
            <!-- Header -->
            <div class="p-4 border-b border-gray-200 flex items-center justify-between bg-gradient-to-r from-blue-600 to-blue-700 text-white">
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
                <div v-if="notifications.length === 0" class="p-8 text-center text-gray-500">
                    <svg
                        class="w-12 h-12 mx-auto mb-2 text-gray-300"
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
                    @click="handleNotificationClick(notification)"
                    :class="[
                        'p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors',
                        !notification.read_at ? 'bg-blue-50' : '',
                    ]"
                >
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-sm font-semibold">
                                {{ getInitials(notification.data?.user_name || 'U') }}
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                {{ notification.data?.user_name || 'Someone' }}
                            </p>
                            <p class="text-sm text-gray-600 mt-1 line-clamp-2">
                                {{ notification.data?.body || 'New message' }}
                            </p>
                            <p class="text-xs text-gray-400 mt-1">
                                {{ formatTime(notification.created_at) }}
                            </p>
                        </div>
                        <div v-if="!notification.read_at" class="flex-shrink-0">
                            <div class="w-2 h-2 bg-blue-500 rounded-full"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div v-if="notifications.length > 0" class="p-2 border-t border-gray-200 text-center">
                <button
                    @click="viewAllNotifications"
                    class="text-sm text-blue-600 hover:text-blue-700 font-medium"
                >
                    View all notifications
                </button>
            </div>
        </div>
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
let clickOutsideHandler = null;

const unreadCount = computed(() => {
    return props.notifications.filter((n) => !n.read_at).length;
});

const toggleDropdown = () => {
    showDropdown.value = !showDropdown.value;
};

const closeDropdown = () => {
    showDropdown.value = false;
};

// Click outside directive
const vClickOutside = {
    mounted(el, binding) {
        clickOutsideHandler = (event) => {
            if (!el.contains(event.target)) {
                binding.value();
            }
        };
        document.addEventListener('click', clickOutsideHandler);
    },
    unmounted() {
        if (clickOutsideHandler) {
            document.removeEventListener('click', clickOutsideHandler);
        }
    },
};

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
    return date.toLocaleDateString();
};

const handleNotificationClick = async (notification) => {
    // Mark as read
    if (!notification.read_at) {
        try {
            await axios.post(`/notifications/${notification.id}/read`);
            emit('notification-read', notification.id);
        } catch (error) {
            console.error('Error marking notification as read:', error);
        }
    }

    // Navigate to conversation
    const conversationId = notification.data?.conversation_id;
    if (conversationId) {
        closeDropdown();
        router.visit(`/conversations/${conversationId}`);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/mark-all-read');
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
    if (clickOutsideHandler) {
        document.removeEventListener('click', clickOutsideHandler);
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

