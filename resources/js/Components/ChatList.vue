<template>
    <div class="w-64 bg-white border-r border-gray-200 flex flex-col shadow-sm">
        <div class="p-4 border-b border-gray-200 bg-gray-50">
            <div class="flex items-center justify-between mb-2">
                <h2 class="text-lg font-semibold text-gray-800">Conversations</h2>
                <button
                    @click="showNewConversationModal = true"
                    class="text-blue-600 hover:text-blue-700 text-sm font-semibold bg-blue-50 hover:bg-blue-100 px-3 py-1.5 rounded-lg transition-colors"
                    title="New conversation"
                >
                    + New
                </button>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto">
            <div
                v-for="conv in conversations"
                :key="conv.id"
                @click="selectConversation(conv.id)"
                :class="[
                    'p-4 border-b border-gray-100 cursor-pointer transition-all',
                    currentConversationId === conv.id 
                        ? 'bg-gradient-to-r from-blue-50 to-white border-l-4 border-l-blue-500 shadow-sm' 
                        : 'hover:bg-gray-50',
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
                <p class="mb-2">No conversations yet</p>
                <button
                    @click="showNewConversationModal = true"
                    class="text-blue-600 hover:text-blue-800 text-sm font-medium underline"
                >
                    Start a conversation
                </button>
            </div>
        </div>

        <!-- New Conversation Modal -->
        <div
            v-if="showNewConversationModal"
            class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50"
            @click.self="showNewConversationModal = false"
        >
            <div class="bg-white rounded-lg p-6 w-full max-w-md mx-4">
                <h3 class="text-lg font-semibold mb-4">Start New Conversation</h3>
                <form @submit.prevent="createConversation">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Select users to chat with:
                        </label>
                        <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-2">
                            <label
                                v-for="user in availableUsers"
                                :key="user.id"
                                class="flex items-center p-2 hover:bg-gray-50 cursor-pointer rounded"
                            >
                                <input
                                    type="checkbox"
                                    :value="user.id"
                                    v-model="selectedUserIds"
                                    class="mr-2"
                                />
                                <span class="text-sm">{{ user.name }}</span>
                                <span class="text-xs text-gray-500 ml-2">({{ user.email }})</span>
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
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500"
                        />
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button
                            type="button"
                            @click="showNewConversationModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-md hover:bg-gray-200"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || selectedUserIds.length === 0"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 rounded-md hover:bg-blue-700 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Creating...' : 'Create' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    conversations: {
        type: Array,
        default: () => [],
    },
    currentConversationId: {
        type: Number,
        default: null,
    },
    availableUsers: {
        type: Array,
        default: () => [],
    },
});

const showNewConversationModal = ref(false);
const selectedUserIds = ref([]);

const form = useForm({
    user_ids: [],
    title: '',
    is_group: false,
});

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

