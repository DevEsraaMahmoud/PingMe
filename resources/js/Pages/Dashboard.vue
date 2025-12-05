<template>
    <div class="min-h-screen bg-gray-50 dark:bg-gray-900 transition-colors duration-200">
        <!-- Header -->
        <header class="bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700 shadow-sm transition-colors duration-200">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">PingMe</h1>
                        <span class="ml-3 text-sm text-gray-500 dark:text-gray-400">Real-time Chat Dashboard</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <!-- Dark Mode Toggle -->
                        <button
                            type="button"
                            @click="toggleDarkMode"
                            class="p-2 text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-800 rounded-lg transition-colors duration-150"
                            :title="isDark ? 'Switch to light mode' : 'Switch to dark mode'"
                        >
                            <svg v-if="!isDark" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z" />
                            </svg>
                            <svg v-else class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z" />
                            </svg>
                        </button>
                        <!-- Notification Bell -->
                        <NotificationDropdown
                            :notifications="localNotifications"
                            @notification-read="handleNotificationRead"
                            @refresh="refreshNotifications"
                        />
                        <!-- New Conversation Button -->
                        <button
                            @click="showNewConversationModal = true"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-150"
                        >
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                            </svg>
                            New Conversation
                        </button>
                        <!-- Logout -->
                        <form @submit.prevent="logout" class="inline">
                            <button
                                type="submit"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 dark:focus:ring-offset-gray-800 transition-colors duration-150"
                            >
                                Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Dashboard Layout -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="grid grid-cols-12 gap-6 h-[calc(100vh-8rem)]">
                <!-- Left Column: Chat List -->
                <div class="col-span-12 lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col transition-colors duration-200">
                    <ChatList
                        :conversations="conversations"
                        :current-conversation-id="conversation?.id"
                        @select-conversation="selectConversation"
                    />
                </div>

                <!-- Middle Column: Chat Window -->
                <div class="col-span-12 lg:col-span-6 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col transition-colors duration-200">
                    <ChatWindow
                        v-if="conversation"
                        :conversation="conversation"
                        :messages="localMessages"
                        :current-user-id="currentUserId"
                        @message-sent="addMessage"
                    />
                    <div v-else class="flex-1 flex items-center justify-center text-gray-500">
                        <div class="text-center">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                            </svg>
                            <p class="text-lg font-medium">Select a conversation to start chatting</p>
                            <p class="text-sm mt-2">Or create a new conversation to get started</p>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Participants -->
                <div class="col-span-12 lg:col-span-3 bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 overflow-hidden flex flex-col transition-colors duration-200">
                    <Participants
                        v-if="conversation"
                        :participants="conversation.participants || []"
                        :current-user-id="currentUserId"
                        :conversation-id="conversation.id"
                        @participant-added="refreshConversation"
                        @participant-removed="refreshConversation"
                    />
                    <div v-else class="flex-1 flex items-center justify-center text-gray-500 p-6">
                        <div class="text-center">
                            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                            <p class="text-sm">Select a conversation to see participants</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- New Conversation Modal -->
        <div
            v-if="showNewConversationModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
            @click.self="showNewConversationModal = false"
        >
            <div class="bg-white rounded-lg shadow-xl w-full max-w-md">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Start New Conversation</h3>
                    <form @submit.prevent="createConversation">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select users to chat with:
                            </label>
                            <div class="max-h-64 overflow-y-auto border border-gray-300 rounded-lg p-2">
                                <label
                                    v-for="user in availableUsers"
                                    :key="user.id"
                                    class="flex items-center p-3 hover:bg-gray-50 cursor-pointer rounded-lg transition-colors duration-150"
                                >
                                    <input
                                        type="checkbox"
                                        :value="user.id"
                                        v-model="selectedUserIds"
                                        class="mr-3 h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                                    />
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-900">{{ user.name }}</span>
                                        <span class="text-xs text-gray-500 ml-2">({{ user.email }})</span>
                                    </div>
                                </label>
                            </div>
                            <div v-if="form.errors.user_ids" class="mt-1 text-sm text-red-600">
                                {{ form.errors.user_ids }}
                            </div>
                        </div>

                        <div class="mb-4" v-if="selectedUserIds.length > 1">
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Group Name (optional):
                            </label>
                            <input
                                type="text"
                                v-model="form.title"
                                placeholder="Enter group name"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500"
                            />
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button
                                type="button"
                                @click="showNewConversationModal = false"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg hover:bg-gray-200 transition-colors duration-150"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                :disabled="form.processing || selectedUserIds.length === 0"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors duration-150"
                            >
                                {{ form.processing ? 'Creating...' : 'Create' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import ChatList from '../Components/ChatList.vue';
import ChatWindow from '../Components/ChatWindow.vue';
import Participants from '../Components/Participants.vue';
import NotificationDropdown from '../Components/NotificationDropdown.vue';
import { showNotification, requestNotificationPermission } from '../services/notificationService';
import { useDarkMode } from '../composables/useDarkMode';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    conversation: {
        type: Object,
        default: null,
    },
    messages: {
        type: Array,
        default: () => [],
    },
    currentUserId: {
        type: Number,
        required: true,
    },
    availableUsers: {
        type: Array,
        default: () => [],
    },
    notifications: {
        type: Array,
        default: () => [],
    },
});

const { isDark, toggleDarkMode, setupSystemPreferenceWatcher } = useDarkMode();

// Setup system preference watcher on mount
onMounted(() => {
    const cleanup = setupSystemPreferenceWatcher();
    if (cleanup) {
        onUnmounted(cleanup);
    }
});

const localMessages = ref([...props.messages]);
const localNotifications = ref([...props.notifications]);
const showNewConversationModal = ref(false);
const selectedUserIds = ref([]);
let echoChannel = null;
let notificationChannel = null;
let presenceChannel = null;

const form = useForm({
    user_ids: [],
    title: '',
    is_group: false,
});

const logout = () => {
    router.post('/logout');
};

const selectConversation = (id) => {
    router.visit(`/conversations/${id}`, {
        preserveState: true,
        preserveScroll: true,
    });
};

const createConversation = () => {
    form.user_ids = selectedUserIds.value;
    form.is_group = selectedUserIds.value.length > 1;
    
    form.post('/conversations', {
        preserveScroll: true,
        onSuccess: () => {
            showNewConversationModal.value = false;
            selectedUserIds.value = [];
            form.reset();
        },
    });
};

const addMessage = (message) => {
    const exists = localMessages.value.some((m) => m.id === message.id);
    if (!exists) {
        localMessages.value.push(message);
    }
};

const handleNotificationRead = (notificationId) => {
    const notification = localNotifications.value.find((n) => n.id === notificationId);
    if (notification) {
        notification.read_at = new Date().toISOString();
    }
};

const refreshNotifications = async () => {
    try {
        const response = await axios.get('/notifications');
        if (response.data && response.data.notifications) {
            localNotifications.value = response.data.notifications;
        }
    } catch (error) {
        console.error('Error refreshing notifications:', error);
    }
};

const refreshConversation = () => {
    if (props.conversation?.id) {
        router.reload({ only: ['conversation', 'participants'] });
    }
};

// Setup Echo channels
const setupChannel = (conversationId) => {
    if (!window.Echo || !conversationId) return;
    
    try {
        // Clean up previous channels
        if (echoChannel) {
            echoChannel.stopListening('.MessageSent');
            echoChannel.stopListeningForWhisper('typing');
            window.Echo.leave(`private-conversation.${props.conversation?.id}`);
        }
        if (presenceChannel) {
            window.Echo.leave(`presence-conversation.${props.conversation?.id}`);
        }

        // Private channel for messages
        echoChannel = window.Echo.private(`conversation.${conversationId}`);
        
        echoChannel.subscribed(() => {
            console.log('✅ Subscribed to conversation channel:', conversationId);
        });

        echoChannel.listen('.MessageSent', (e) => {
            console.log('📨 Message received:', e);
            const messageData = e.message || e;
            addMessage(messageData);
            
            // Play sound and show notification only for recipient
            if (messageData.user?.id !== props.currentUserId) {
                handleIncomingMessage(messageData);
            }
        });

        // Presence channel for online status
        presenceChannel = window.Echo.join(`presence-conversation.${conversationId}`)
            .here((users) => {
                console.log('👥 Users here:', users);
            })
            .joining((user) => {
                console.log('➕ User joining:', user);
            })
            .leaving((user) => {
                console.log('➖ User leaving:', user);
            });
    } catch (error) {
        console.error('❌ Error setting up channels:', error);
    }
};

const handleIncomingMessage = async (messageData) => {
    // Don't show notification if user is viewing the conversation
    if (props.conversation?.id === messageData.conversation_id) {
        return;
    }

    // Play sound
    const { playNotificationSound } = await import('../services/notificationService');
    playNotificationSound();

    // Show browser notification
    const hasPermission = await requestNotificationPermission();
    if (hasPermission) {
        const userName = messageData.user?.name || 'Someone';
        const messageBody = messageData.body || 'New message';
        const conversationId = messageData.conversation_id;

        await showNotification(`${userName} sent a message`, {
            body: messageBody.length > 100 ? messageData.body.substring(0, 100) + '...' : messageBody,
            tag: `conversation-${conversationId}`,
            onClick: () => {
                if (conversationId) {
                    router.visit(`/conversations/${conversationId}`);
                }
            },
        });
    }
};

const setupNotificationChannel = () => {
    if (!window.Echo || !props.currentUserId) return;

    try {
        if (notificationChannel) {
            notificationChannel.stopListening('.notification.sent');
            window.Echo.leave(`private-user.${props.currentUserId}`);
        }

        notificationChannel = window.Echo.private(`user.${props.currentUserId}`);
        
        notificationChannel.subscribed(() => {
            console.log('✅ Subscribed to notification channel');
        });

        notificationChannel.notification((notification) => {
            console.log('🔔 Notification received:', notification);
            if (notification && notification.data) {
                const newNotification = {
                    id: notification.id || `temp-${Date.now()}`,
                    data: notification.data,
                    read_at: null,
                    created_at: new Date().toISOString(),
                };
                localNotifications.value.unshift(newNotification);
                if (localNotifications.value.length > 20) {
                    localNotifications.value = localNotifications.value.slice(0, 20);
                }
            }
        });
    } catch (error) {
        console.error('❌ Error setting up notification channel:', error);
    }
};

// Watch for conversation changes
watch(
    () => props.conversation?.id,
    (conversationId) => {
        if (conversationId) {
            setupChannel(conversationId);
        }
    },
    { immediate: true }
);

// Watch for messages
watch(
    () => props.messages,
    (newMessages) => {
        localMessages.value = [...newMessages];
    },
    { deep: true }
);

// Watch for notifications
watch(
    () => props.notifications,
    (newNotifications) => {
        localNotifications.value = [...newNotifications];
    },
    { deep: true }
);

onMounted(() => {
    setupNotificationChannel();
    refreshNotifications();
    
    // Update last_active_at every minute
    const updateActivity = () => {
        axios.post('/api/user/activity').catch(() => {});
    };
    updateActivity();
    const activityInterval = setInterval(updateActivity, 60000);
    
    onUnmounted(() => {
        clearInterval(activityInterval);
    });
});

onUnmounted(() => {
    if (echoChannel) {
        echoChannel.stopListening('.MessageSent');
        window.Echo.leave(`private-conversation.${props.conversation?.id}`);
    }
    if (presenceChannel) {
        window.Echo.leave(`presence-conversation.${props.conversation?.id}`);
    }
    if (notificationChannel) {
        notificationChannel.stopListening('.notification.sent');
        window.Echo.leave(`private-user.${props.currentUserId}`);
    }
});
</script>

