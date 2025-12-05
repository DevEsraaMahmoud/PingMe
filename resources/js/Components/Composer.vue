<template>
    <div class="p-4 border-t border-gray-200 bg-white shadow-lg">
        <form @submit.prevent="sendMessage" class="flex items-end space-x-3">
            <div class="flex-1 relative">
                <textarea
                    ref="textareaRef"
                    v-model="messageBody"
                    @keydown.enter.exact.prevent="sendMessage"
                    @keydown.enter.shift.exact="handleShiftEnter"
                    @input="handleInput"
                    :style="{ height: textareaHeight + 'px', minHeight: '44px', maxHeight: '120px' }"
                    class="w-full px-4 py-3 pr-12 border border-gray-300 rounded-2xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none overflow-hidden transition-all"
                    placeholder="Type a message..."
                    @focus="handleFocus"
                ></textarea>
            </div>
            <button
                type="submit"
                :disabled="!messageBody.trim() || sending"
                class="flex-shrink-0 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-2xl hover:from-blue-600 hover:to-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md hover:shadow-lg font-medium"
            >
                <span v-if="sending" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sending...
                </span>
                <span v-else>Send</span>
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    conversationId: {
        type: Number,
        required: true,
    },
});

const emit = defineEmits(['message-sent']);

const messageBody = ref('');
const sending = ref(false);
const textareaRef = ref(null);
const textareaHeight = ref(44);
let typingTimeout = null;
let echoChannel = null;

// Initialize Echo channel for typing indicators
onMounted(() => {
    if (window.Echo) {
        echoChannel = window.Echo.private(`conversation.${props.conversationId}`);
    }
});

onUnmounted(() => {
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
});

const stopTypingIndicator = () => {
    if (echoChannel && typingTimeout) {
        clearTimeout(typingTimeout);
        typingTimeout = null;
        
        // Notify others that typing has stopped
        const user = window.Laravel?.user || { id: null, name: 'Someone' };
        echoChannel.whisper('typing', {
            user: {
                id: user.id,
                name: user.name,
            },
            stopped: true,
        });
    }
};

const adjustTextareaHeight = () => {
    if (textareaRef.value) {
        // Reset height to auto to get the correct scrollHeight
        textareaRef.value.style.height = 'auto';
        const scrollHeight = textareaRef.value.scrollHeight;
        // Set height based on content, with min and max limits
        textareaHeight.value = Math.min(Math.max(scrollHeight, 44), 120);
    }
};

const handleInput = () => {
    adjustTextareaHeight();
    handleTyping();
};

const handleShiftEnter = () => {
    messageBody.value += '\n';
    adjustTextareaHeight();
};

const handleFocus = () => {
    // Prevent page scroll when focusing textarea
    if (textareaRef.value) {
        textareaRef.value.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

const handleTyping = () => {
    if (echoChannel) {
        // Get user info from Inertia shared props (available via window)
        const user = window.Laravel?.user || { id: null, name: 'Someone' };
        
        echoChannel.whisper('typing', {
            user: {
                id: user.id,
                name: user.name,
            },
        });

        // Clear previous timeout
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }

        // Stop typing indicator after 3 seconds of inactivity
        typingTimeout = setTimeout(() => {
            stopTypingIndicator();
        }, 3000);
    }
};

const sendMessage = async () => {
    if (!messageBody.value.trim() || sending.value) {
        return;
    }

    // Clear typing indicator immediately when sending
    stopTypingIndicator();

    const body = messageBody.value.trim();
    messageBody.value = '';
    textareaHeight.value = 44; // Reset textarea height
    sending.value = true;

    try {
        const response = await axios.post(`/conversations/${props.conversationId}/messages`, {
            body: body,
            type: 'text',
        });

        emit('message-sent', response.data.message);
    } catch (error) {
        console.error('Error sending message:', error);
        // Restore message body on error
        messageBody.value = body;
        alert('Failed to send message. Please try again.');
    } finally {
        sending.value = false;
    }
};
</script>

