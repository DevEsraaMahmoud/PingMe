<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Header with Logout -->
        <div class="bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-lg px-6 py-4 flex justify-between items-center">
            <h1 class="text-xl font-bold">PingMe</h1>
            <div class="flex items-center space-x-4">
                <!-- Notification Dropdown -->
                <NotificationDropdown
                    :notifications="localNotifications"
                    @notification-read="handleNotificationRead"
                    @refresh="refreshNotifications"
                />
                <!-- Logout Button -->
                <form @submit.prevent="logout" class="inline">
                    <button
                        type="submit"
                        class="text-sm bg-white bg-opacity-20 hover:bg-opacity-30 px-4 py-2 rounded-lg transition-all font-medium text-black"
                    >
                        Logout
                    </button>
                </form>
            </div>
        </div>
        <div class="flex" style="height: calc(100vh - 73px);">
            <!-- Chat List Sidebar -->
            <ChatList
                :conversations="conversations"
                :current-conversation-id="conversation?.id"
                :available-users="availableUsers || []"
            />

            <!-- Chat Window -->
            <div class="flex-1 flex flex-col h-full">
                <ChatWindow
                    v-if="conversation"
                    :conversation="conversation"
                    :messages="localMessages"
                    :current-user-id="currentUserId"
                    @message-sent="addMessage"
                />
                <div v-else class="flex-1 flex items-center justify-center text-gray-500">
                    <div class="text-center">
                        <p class="text-lg">Select a conversation to start chatting</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';
import ChatList from '../Components/ChatList.vue';
import ChatWindow from '../Components/ChatWindow.vue';
import NotificationDropdown from '../Components/NotificationDropdown.vue';
import { showNotification, requestNotificationPermission } from '../services/notificationService';

const logout = () => {
    router.post('/logout');
};

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

const localMessages = ref([...props.messages]);
const localNotifications = ref([...props.notifications]);
let echoChannel = null;
let notificationChannel = null;

// Function to add message to local messages
const addMessage = (message) => {
    const exists = localMessages.value.some((m) => m.id === message.id);
    if (!exists) {
        localMessages.value.push(message);
    }
};

// Update local messages when props.messages changes
watch(
    () => props.messages,
    (newMessages) => {
        localMessages.value = [...newMessages];
    },
    { deep: true }
);

// Update local notifications when props.notifications changes
watch(
    () => props.notifications,
    (newNotifications) => {
        localNotifications.value = [...newNotifications];
    },
    { deep: true }
);

// Handle incoming notification
const handleNotification = async (notificationData) => {
    console.log('📬 Handling notification:', notificationData);
    
    // Extract data - Laravel notification structure: { id, type, data, read_at }
    const data = notificationData.data || notificationData;
    
    // Add notification to local list (prepend to show newest first)
    const newNotification = {
        id: notificationData.id || `temp-${Date.now()}`,
        data: data,
        read_at: null,
        created_at: new Date().toISOString(),
    };
    localNotifications.value.unshift(newNotification);
    
    // Keep only last 20 notifications
    if (localNotifications.value.length > 20) {
        localNotifications.value = localNotifications.value.slice(0, 20);
    }
    
    // Don't show browser notification if user is viewing the conversation
    if (props.conversation?.id === data.conversation_id) {
        console.log('ℹ️ User is viewing this conversation, skipping browser notification');
        return;
    }

    // Request permission if needed
    const hasPermission = await requestNotificationPermission();
    if (!hasPermission) {
        console.warn('⚠️ Browser notification permission not granted');
        // Still play sound even without permission
        const { playNotificationSound } = await import('../services/notificationService');
        playNotificationSound();
        return;
    }

    // Show browser notification
    const userName = data.user_name || 'Someone';
    const messageBody = data.body || 'New message';
    const conversationId = data.conversation_id;

    console.log('🔔 Showing browser notification:', { userName, messageBody, conversationId });

    await showNotification(`${userName} sent a message`, {
        body: messageBody.length > 100 ? messageBody.substring(0, 100) + '...' : messageBody,
        tag: `conversation-${conversationId}`,
        onClick: () => {
            // Navigate to conversation when notification is clicked
            if (conversationId) {
                router.visit(`/conversations/${conversationId}`);
            }
        },
    });
};

// Handle notification read
const handleNotificationRead = (notificationId) => {
    const notification = localNotifications.value.find((n) => n.id === notificationId);
    if (notification) {
        notification.read_at = new Date().toISOString();
    }
};

// Refresh notifications
const refreshNotifications = async () => {
    try {
        const response = await axios.get('/notifications');
        localNotifications.value = response.data.notifications || [];
    } catch (error) {
        console.error('Error refreshing notifications:', error);
    }
};

// Setup notification channel for real-time notifications
const setupNotificationChannel = () => {
    if (!window.Echo || !props.currentUserId) {
        return;
    }

    try {
        // Clean up previous notification channel
        if (notificationChannel) {
            notificationChannel.stopListening('.notification.sent');
            window.Echo.leave(`private-user.${props.currentUserId}`);
            notificationChannel = null;
        }

        notificationChannel = window.Echo.private(`user.${props.currentUserId}`);

        notificationChannel.subscribed(() => {
            console.log('✅ Successfully subscribed to notification channel');
        });

        notificationChannel.error((error) => {
            console.error('❌ Notification channel subscription error:', error);
        });

        // Laravel broadcasts notifications using the notification() method
        // This listens for ALL notifications on this channel
        notificationChannel.notification((notification) => {
            console.log('🔔 Notification received via notification() method:', notification);
            
            // Laravel notification structure: { id, type, data, read_at }
            if (notification && notification.data) {
                handleNotification(notification);
            } else {
                console.warn('⚠️ Unexpected notification format:', notification);
            }
        });

        // Also listen for specific event names (fallback)
        const notificationHandler = (data) => {
            console.log('🔔 Notification received via listen():', data);
            if (data.data) {
                handleNotification(data);
            } else if (data.notification && data.notification.data) {
                handleNotification(data.notification);
            }
        };

        // Try multiple event name formats as fallback
        notificationChannel.listen('.App\\Notifications\\MessageNotification', notificationHandler);
        notificationChannel.listen('.App.Notifications.MessageNotification', notificationHandler);
        notificationChannel.listen('.MessageNotification', notificationHandler);
        notificationChannel.listen('.notification.sent', notificationHandler);

        console.log('👂 Listening for notifications on channel:', `user.${props.currentUserId}`);
    } catch (error) {
        console.error('❌ Error setting up notification channel:', error);
    }
};

// Function to setup Echo channel
const setupChannel = (conversationId) => {
    console.log('🔔 Setting up Echo listener for conversation:', conversationId);
    
    try {
        echoChannel = window.Echo.private(`conversation.${conversationId}`);

        // Wait for subscription before listening
        echoChannel.subscribed(() => {
            console.log('✅ Successfully subscribed to conversation channel:', conversationId);
        });

        echoChannel.error((error) => {
            console.error('❌ Channel subscription error:', error);
            if (error.status === 403) {
                console.error('⚠️ Authorization failed! Check routes/channels.php');
            }
        });

        // Listen for MessageSent event
        // Try multiple event name formats to catch the broadcast
        const eventHandler = (e) => {
            console.log('📨 Message received via broadcast:', e);
            // The event data structure from broadcastWith wraps in 'message'
            const messageData = e.message || e;
            console.log('📝 Adding message to localMessages:', messageData);
            addMessage(messageData);
        };
        
        // Listen with dot prefix (Laravel namespaced format)
        echoChannel.listen('.MessageSent', eventHandler);
        // Also listen without dot (fallback)
        echoChannel.listen('MessageSent', eventHandler);

        // Listen for typing indicators
        echoChannel.listenForWhisper('typing', (e) => {
            console.log('⌨️ User typing:', e);
        });

        console.log('👂 Listening for events on channel:', `conversation.${conversationId}`);
    } catch (error) {
        console.error('❌ Error setting up channel:', error);
    }
};

// Setup notification channel on mount (independent of conversation)
onMounted(() => {
    // Setup notification channel immediately when component mounts
    if (window.Echo && props.currentUserId) {
        setupNotificationChannel();
    } else {
        // Wait for Echo to be available
        const checkEcho = setInterval(() => {
            if (window.Echo && props.currentUserId) {
                setupNotificationChannel();
                clearInterval(checkEcho);
            }
        }, 500);
        
        // Clear interval after 10 seconds
        setTimeout(() => clearInterval(checkEcho), 10000);
    }
});

// Subscribe to conversation channel when conversation changes
watch(
    () => props.conversation?.id,
    (conversationId) => {
        // Clean up previous channel
        if (echoChannel) {
            echoChannel.stopListening('.MessageSent');
            echoChannel.stopListeningForWhisper('typing');
            if (props.conversation?.id) {
                window.Echo.leave(`private-conversation.${props.conversation.id}`);
            }
            echoChannel = null;
        }

        if (!conversationId) {
            return;
        }

        // Wait for Echo to be available
        if (!window.Echo) {
            console.error('❌ Echo is not initialized! Retrying...');
            // Retry after a short delay
            setTimeout(() => {
                if (window.Echo && props.conversation?.id === conversationId) {
                    setupChannel(conversationId);
                }
            }, 1000);
            return;
        }

        setupChannel(conversationId);
    },
    { immediate: true }
);

onUnmounted(() => {
    if (echoChannel) {
        echoChannel.stopListening('.MessageSent');
        echoChannel.stopListeningForWhisper('typing');
        if (props.conversation?.id) {
            window.Echo.leave(`private-conversation.${props.conversation.id}`);
        }
    }
    if (notificationChannel) {
        notificationChannel.stopListening('.notification.sent');
        if (props.currentUserId) {
            window.Echo.leave(`private-user.${props.currentUserId}`);
        }
    }
});
</script>

