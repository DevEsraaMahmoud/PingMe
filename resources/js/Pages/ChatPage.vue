<template>
    <div class="min-h-screen bg-gray-50">
        <div class="flex h-screen">
            <!-- Chat List Sidebar -->
            <ChatList :conversations="conversations" :current-conversation-id="conversation?.id" />

            <!-- Chat Window -->
            <div class="flex-1 flex flex-col">
            <ChatWindow
                v-if="conversation"
                :conversation="conversation"
                :messages="localMessages"
                :current-user-id="currentUserId"
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
import ChatList from '../Components/ChatList.vue';
import ChatWindow from '../Components/ChatWindow.vue';

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
});

const localMessages = ref([...props.messages]);
let echoChannel = null;

// Update local messages when props.messages changes
watch(
    () => props.messages,
    (newMessages) => {
        localMessages.value = [...newMessages];
    },
    { deep: true }
);

// Subscribe to conversation channel when conversation changes
watch(
    () => props.conversation?.id,
    (conversationId) => {
        if (echoChannel) {
            echoChannel.stopListening('MessageSent');
            if (props.conversation?.id) {
                window.Echo.leave(`private-conversation.${props.conversation.id}`);
            }
        }

        if (conversationId && window.Echo) {
            echoChannel = window.Echo.private(`conversation.${conversationId}`);

            echoChannel.listen('.MessageSent', (e) => {
                // Add new message to local messages if not already present
                const exists = localMessages.value.some((m) => m.id === e.message.id);
                if (!exists) {
                    localMessages.value.push(e.message);
                }
            });

            // Listen for typing indicators (optional)
            echoChannel.listenForWhisper('typing', (e) => {
                // Handle typing indicator
                console.log('User typing:', e);
            });
        }
    },
    { immediate: true }
);

onUnmounted(() => {
    if (echoChannel) {
        echoChannel.stopListening('MessageSent');
        if (props.conversation?.id) {
            window.Echo.leave(`private-conversation.${props.conversation.id}`);
        }
    }
});
</script>

