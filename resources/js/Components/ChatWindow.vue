<template>
    <div class="flex-1 flex flex-col h-full bg-white dark:bg-gray-800 overflow-hidden transition-colors duration-200">
        <!-- Header - Fixed at top -->
        <div class="flex-shrink-0 px-6 py-4 border-b border-gray-200 dark:border-gray-700 bg-gradient-to-r from-blue-600 via-blue-700 to-purple-600 dark:from-blue-700 dark:via-blue-800 dark:to-purple-700 text-white shadow-lg transition-colors duration-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-12 h-12 rounded-full bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 flex items-center justify-center text-white text-sm font-bold shadow-md flex-shrink-0">
                        {{ getInitials(getConversationTitle()) }}
                    </div>
                    <div>
                        <h2 class="text-xl font-bold text-white">
                            {{ getConversationTitle() }}
                        </h2>
                        <!-- <p v-if="conversation.participants" class="text-sm text-blue-100 mt-1">
                            {{ conversation.participants.length }} participant(s)
                        </p> -->
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages - Scrollable area -->
        <div 
            ref="messagesContainer" 
            class="flex-1 overflow-y-auto p-6 space-y-4 bg-gradient-to-b from-gray-50 via-white to-gray-50 dark:from-gray-900 dark:via-gray-800 dark:to-gray-900 transition-colors duration-200"
            style="scroll-behavior: smooth;"
        >
            <MessageBubble
                v-for="message in messages"
                :key="message.id"
                :message="message"
                :is-own="message.user.id === currentUserId"
            />
            <div v-if="typingUsers.length > 0" class="flex items-center space-x-2 text-sm text-gray-500 dark:text-gray-400 italic px-4 py-2">
                <div class="flex space-x-1">
                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0s;"></div>
                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.2s;"></div>
                    <div class="w-2 h-2 bg-gray-400 dark:bg-gray-500 rounded-full animate-bounce" style="animation-delay: 0.4s;"></div>
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

let typingChannel = null;

// Listen for typing indicators and scroll to bottom on mount
onMounted(() => {
    // Scroll to bottom when component first loads
    scrollToBottom(true);
    
    // Setup typing indicators
    if (window.Echo && props.conversation?.id) {
        typingChannel = window.Echo.private(`conversation.${props.conversation.id}`);
        
        typingChannel.subscribed(() => {
            console.log('✅ ChatWindow subscribed to conversation channel for typing');
        });
        
        typingChannel.listenForWhisper('typing', (e) => {
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
            
            // Clear existing timeout for this user - don't auto-remove
            // Typing indicator will only disappear when stopped event is received
            if (typingTimeouts[userName]) {
                clearTimeout(typingTimeouts[userName]);
                delete typingTimeouts[userName];
            }
        });
    }
});

onUnmounted(() => {
    // Clear all typing timeouts
    Object.values(typingTimeouts).forEach(timeout => clearTimeout(timeout));
    typingTimeouts = {};
    
    // Leave typing channel if it exists
    if (typingChannel && props.conversation?.id) {
        typingChannel.stopListeningForWhisper('typing');
    }
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

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const getConversationTitle = () => {
    if (props.conversation.title) {
        return props.conversation.title;
    }
    
    // For 1-on-1 conversations, get the other participant's name
    if (props.conversation.participants && props.conversation.participants.length > 0) {
        const otherParticipant = props.conversation.participants.find(p => p.id !== props.currentUserId);
        return otherParticipant ? otherParticipant.name : 'Chat';
    }
    
    return 'Chat';
};
</script>

