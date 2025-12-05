<template>
    <div class="flex flex-col h-full bg-gradient-to-b from-gray-50 to-white dark:from-gray-800 dark:to-gray-900 transition-colors duration-200">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 transition-colors duration-200">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Participants</h3>
        </div>

        <!-- Participants List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-2">
            <div
                v-for="participant in participants"
                :key="participant.id"
                class="group flex items-center justify-between p-3 rounded-lg hover:bg-white dark:hover:bg-gray-700 transition-all duration-150 border border-transparent hover:border-gray-200 dark:hover:border-gray-600 hover:shadow-sm"
            >
                <div class="flex items-center space-x-3 flex-1 min-w-0">
                    <div class="relative flex-shrink-0">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 flex items-center justify-center text-white text-sm font-bold shadow-md">
                            {{ getInitials(participant.name) }}
                        </div>
                        <!-- Online Status Indicator -->
                        <div
                            v-if="isOnline(participant.id)"
                            class="absolute bottom-0 right-0 w-4 h-4 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full shadow-sm"
                            title="Online"
                        ></div>
                        <div
                            v-else-if="isAway(participant.id)"
                            class="absolute bottom-0 right-0 w-4 h-4 bg-yellow-500 border-2 border-white dark:border-gray-800 rounded-full shadow-sm"
                            title="Away"
                        ></div>
                        <div
                            v-else
                            class="absolute bottom-0 right-0 w-4 h-4 bg-gray-400 dark:bg-gray-600 border-2 border-white dark:border-gray-800 rounded-full shadow-sm"
                            title="Offline"
                        ></div>
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {{ participant.name }}
                            <span v-if="participant.id === currentUserId" class="text-xs font-normal text-gray-500 dark:text-gray-400">(You)</span>
                        </p>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ participant.email }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';

const props = defineProps({
    participants: {
        type: Array,
        default: () => [],
    },
    currentUserId: {
        type: Number,
        required: true,
    },
    conversationId: {
        type: Number,
        required: true,
    },
});

const onlineUsers = ref([]);
let presenceChannel = null;

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const isOnline = (userId) => {
    return onlineUsers.value.some((u) => u.id === userId);
};

const isAway = (userId) => {
    // Check if user was active in last 5 minutes
    const user = props.participants.find((p) => p.id === userId);
    if (!user || !user.last_active_at) return false;
    
    const lastActive = new Date(user.last_active_at);
    const now = new Date();
    const diffMinutes = (now - lastActive) / (1000 * 60);
    
    return diffMinutes > 1 && diffMinutes <= 5;
};

const setupPresenceChannel = () => {
    if (!window.Echo || !props.conversationId) return;

    try {
        if (presenceChannel) {
            window.Echo.leave(`presence-conversation.${props.conversationId}`);
        }

        presenceChannel = window.Echo.join(`presence-conversation.${props.conversationId}`)
            .here((users) => {
                console.log('👥 Users here:', users);
                onlineUsers.value = users.filter((u) => u.id !== props.currentUserId);
            })
            .joining((user) => {
                console.log('➕ User joining:', user);
                if (user.id !== props.currentUserId && !onlineUsers.value.find((u) => u.id === user.id)) {
                    onlineUsers.value.push(user);
                }
            })
            .leaving((user) => {
                console.log('➖ User leaving:', user);
                onlineUsers.value = onlineUsers.value.filter((u) => u.id !== user.id);
            });
    } catch (error) {
        console.error('❌ Error setting up presence channel:', error);
    }
};

watch(
    () => props.conversationId,
    (conversationId) => {
        if (conversationId) {
            setupPresenceChannel();
        }
    },
    { immediate: true }
);

onMounted(() => {
    setupPresenceChannel();
});


onUnmounted(() => {
    if (presenceChannel && props.conversationId) {
        window.Echo.leave(`presence-conversation.${props.conversationId}`);
    }
});
</script>

