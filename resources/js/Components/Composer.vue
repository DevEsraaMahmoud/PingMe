<template>
    <div class="p-4 border-t border-gray-200 bg-white">
        <form @submit.prevent="sendMessage" class="flex items-end space-x-2">
            <div class="flex-1">
                <textarea
                    v-model="messageBody"
                    @keydown.enter.exact.prevent="sendMessage"
                    @keydown.enter.shift.exact="messageBody += '\n'"
                    @input="handleTyping"
                    rows="1"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-none"
                    placeholder="Type a message..."
                ></textarea>
            </div>
            <button
                type="submit"
                :disabled="!messageBody.trim() || sending"
                class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
            >
                {{ sending ? 'Sending...' : 'Send' }}
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref } from 'vue';
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
let typingTimeout = null;
let echoChannel = null;

// Initialize Echo channel for typing indicators
if (window.Echo) {
    echoChannel = window.Echo.private(`conversation.${props.conversationId}`);
}

const handleTyping = () => {
    if (echoChannel) {
        echoChannel.whisper('typing', {
            user: 'typing...',
        });

        // Clear previous timeout
        if (typingTimeout) {
            clearTimeout(typingTimeout);
        }

        // Stop typing indicator after 3 seconds
        typingTimeout = setTimeout(() => {
            // Typing stopped
        }, 3000);
    }
};

const sendMessage = async () => {
    if (!messageBody.value.trim() || sending.value) {
        return;
    }

    const body = messageBody.value.trim();
    messageBody.value = '';
    sending.value = true;

    try {
        // Optimistic UI: Add temporary message locally
        // The server broadcast will provide the canonical message

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

