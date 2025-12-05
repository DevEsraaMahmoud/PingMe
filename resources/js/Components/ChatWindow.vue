<template>
    <div class="flex-1 flex flex-col h-full bg-white overflow-hidden">
        <!-- Header - Fixed at top -->
        <div class="flex-shrink-0 p-4 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700 text-white shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-white">
                        {{ conversation.title || 'Chat' }}
                    </h2>
                    <p v-if="conversation.participants" class="text-sm text-blue-100">
                        {{ conversation.participants.length }} participant(s)
                    </p>
                </div>
                <Participants :participants="conversation.participants || []" />
            </div>
        </div>

        <!-- Messages - Scrollable area -->
        <div 
            ref="messagesContainer" 
            class="flex-1 overflow-y-auto p-6 space-y-3 bg-gradient-to-b from-gray-50 to-white"
            style="scroll-behavior: smooth;"
        >
            <MessageBubble
                v-for="message in messages"
                :key="message.id"
                :message="message"
                :is-own="message.user.id === currentUserId"
            />
            <div v-if="typingUsers.length > 0" class="flex items-center space-x-2 text-sm text-gray-500 italic px-4 py-2">
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                    <div class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
                </div>
                <span>{{ typingUsersText }}</span>
            </div>
        </div>

        <!-- Composer - Fixed at bottom -->
        <div class="flex-shrink-0">
            <Composer :conversation-id="conversation.id" @message-sent="handleNewMessage" />
        </div>
    </div>
</template>

<script setup>
import { ref, watch, nextTick, computed, onMounted, onUnmounted } from 'vue';
import MessageBubble from './MessageBubble.vue';
import Composer from './Composer.vue';
import Participants from './Participants.vue';

const props = defineProps({
    conversation: {
        type: Object,
        required: true,
    },
    messages: {
        type: Array,
        default: () => [],
    },
    currentUserId: {
        type: Number,
        required: true,
    },
});

const messagesContainer = ref(null);
const typingUsers = ref([]);
let typingTimeouts = {};

const typingUsersText = computed(() => {
    if (typingUsers.value.length === 0) return '';
    if (typingUsers.value.length === 1) {
        return `${typingUsers.value[0]} is typing...`;
    }
    return `${typingUsers.value.join(', ')} are typing...`;
});

// Listen for typing indicators and scroll to bottom on mount
onMounted(() => {
    // Scroll to bottom when component first loads
    scrollToBottom(true);
    
    // Setup typing indicators
    if (window.Echo && props.conversation?.id) {
        const channel = window.Echo.private(`conversation.${props.conversation.id}`);
        
        channel.listenForWhisper('typing', (e) => {
            // Skip if it's the current user typing
            const currentUser = window.Laravel?.user;
            if (currentUser && e.user?.id === currentUser.id) {
                return;
            }
            
            const userName = e.user?.name || 'Someone';
            
            // Handle stopped typing event
            if (e.stopped) {
                const index = typingUsers.value.indexOf(userName);
                if (index > -1) {
                    typingUsers.value.splice(index, 1);
                }
                if (typingTimeouts[userName]) {
                    clearTimeout(typingTimeouts[userName]);
                    delete typingTimeouts[userName];
                }
                return;
            }
            
            // Add user to typing list
            if (!typingUsers.value.includes(userName)) {
                typingUsers.value.push(userName);
            }
            
            // Clear existing timeout for this user
            if (typingTimeouts[userName]) {
                clearTimeout(typingTimeouts[userName]);
            }
            
            // Remove user from typing list after 3 seconds of inactivity
            typingTimeouts[userName] = setTimeout(() => {
                const index = typingUsers.value.indexOf(userName);
                if (index > -1) {
                    typingUsers.value.splice(index, 1);
                }
                delete typingTimeouts[userName];
            }, 3000);
        });
    }
});

onUnmounted(() => {
    // Clear all typing timeouts
    Object.values(typingTimeouts).forEach(timeout => clearTimeout(timeout));
    typingTimeouts = {};
});

const scrollToBottom = (force = false) => {
    nextTick(() => {
        if (messagesContainer.value) {
            const container = messagesContainer.value;
            const isNearBottom = container.scrollHeight - container.scrollTop - container.clientHeight < 100;
            
            // Always auto-scroll for new messages (force = true)
            // Or auto-scroll if user is already near bottom
            if (force || isNearBottom) {
                container.scrollTo({
                    top: container.scrollHeight,
                    behavior: 'smooth'
                });
            }
        }
    });
};

// Watch for new messages and auto-scroll
watch(
    () => props.messages.length,
    (newLength, oldLength) => {
        // Only auto-scroll if a new message was added
        if (newLength > (oldLength || 0)) {
            scrollToBottom(true);
        }
    },
    { immediate: true }
);

// Watch for new messages added (including broadcasts)
watch(
    () => props.messages,
    (newMessages, oldMessages) => {
        // Check if a new message was added
        if (oldMessages && newMessages.length > oldMessages.length) {
            scrollToBottom(true);
        } else if (!oldMessages && newMessages.length > 0) {
            // Initial load
            scrollToBottom(true);
        }
    },
    { deep: true }
);

const emit = defineEmits(['message-sent']);

const handleNewMessage = (message) => {
    // Emit to parent to add message to localMessages
    emit('message-sent', message);
    // Force scroll to bottom when new message is sent
    scrollToBottom(true);
};
</script>

