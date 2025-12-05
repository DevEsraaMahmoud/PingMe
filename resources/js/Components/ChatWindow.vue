<template>
    <div class="flex-1 flex flex-col bg-white">
        <!-- Header -->
        <div class="p-4 border-b border-gray-200 bg-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-800">
                        {{ conversation.title || 'Chat' }}
                    </h2>
                    <p v-if="conversation.participants" class="text-sm text-gray-500">
                        {{ conversation.participants.length }} participant(s)
                    </p>
                </div>
                <Participants :participants="conversation.participants || []" />
            </div>
        </div>

        <!-- Messages -->
        <div ref="messagesContainer" class="flex-1 overflow-y-auto p-4 space-y-4">
            <MessageBubble
                v-for="message in messages"
                :key="message.id"
                :message="message"
                :is-own="message.user.id === currentUserId"
            />
        </div>

        <!-- Composer -->
        <Composer :conversation-id="conversation.id" @message-sent="handleMessageSent" />
    </div>
</template>

<script setup>
import { ref, watch, nextTick } from 'vue';
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

const scrollToBottom = () => {
    nextTick(() => {
        if (messagesContainer.value) {
            messagesContainer.value.scrollTop = messagesContainer.value.scrollHeight;
        }
    });
};

watch(
    () => props.messages.length,
    () => {
        scrollToBottom();
    },
    { immediate: true }
);

const handleMessageSent = () => {
    scrollToBottom();
};
</script>

