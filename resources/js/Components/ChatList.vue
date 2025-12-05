<template>
    <div class="w-64 bg-white border-r border-gray-200 flex flex-col">
        <div class="p-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Conversations</h2>
        </div>
        <div class="flex-1 overflow-y-auto">
            <div
                v-for="conv in conversations"
                :key="conv.id"
                @click="selectConversation(conv.id)"
                :class="[
                    'p-4 border-b border-gray-100 cursor-pointer hover:bg-gray-50 transition-colors',
                    currentConversationId === conv.id ? 'bg-blue-50 border-l-4 border-l-blue-500' : '',
                ]"
            >
                <div class="flex items-start justify-between">
                    <div class="flex-1 min-w-0">
                        <h3 class="text-sm font-medium text-gray-900 truncate">
                            {{ getConversationTitle(conv) }}
                        </h3>
                        <p v-if="conv.latest_message" class="text-xs text-gray-500 mt-1 truncate">
                            {{ conv.latest_message.user.name }}: {{ conv.latest_message.body }}
                        </p>
                        <p v-else class="text-xs text-gray-400 mt-1">No messages yet</p>
                    </div>
                </div>
                <div class="mt-2 flex items-center justify-between">
                    <span class="text-xs text-gray-400">
                        {{ formatTime(conv.updated_at) }}
                    </span>
                    <span
                        v-if="conv.messages_count > 0"
                        class="text-xs bg-blue-100 text-blue-800 px-2 py-0.5 rounded-full"
                    >
                        {{ conv.messages_count }}
                    </span>
                </div>
            </div>
            <div v-if="conversations.length === 0" class="p-4 text-center text-gray-500 text-sm">
                No conversations yet
            </div>
        </div>
    </div>
</template>

<script setup>
import { router } from '@inertiajs/vue3';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    currentConversationId: {
        type: Number,
        default: null,
    },
});

const getConversationTitle = (conv) => {
    if (conv.title) {
        return conv.title;
    }
    if (conv.is_group) {
        return `Group (${conv.participants.length})`;
    }
    // For 1-on-1 conversations, show the other participant's name
    if (conv.participants && conv.participants.length > 0) {
        return conv.participants[0].name;
    }
    return 'Untitled Conversation';
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

const selectConversation = (id) => {
    router.visit(`/conversations/${id}`, {
        preserveState: true,
        preserveScroll: true,
    });
};
</script>

