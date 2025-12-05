<template>
    <div class="flex flex-col h-full bg-gradient-to-b from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 transition-colors duration-200">
        <div class="p-4 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 shadow-sm transition-colors duration-200">
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Conversations</h2>
            </div>
            <!-- Search Input -->
            <div class="relative">
                <input
                    v-model="searchQuery"
                    type="text"
                    placeholder="Search conversations..."
                    class="w-full px-4 py-2 pl-10 border border-gray-300 dark:border-gray-600 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm shadow-sm transition-all bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-400"
                />
                <svg class="absolute left-3 top-2.5 w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                </svg>
            </div>
        </div>
        <div class="flex-1 overflow-y-auto">
            <div
                v-for="conv in filteredConversations"
                :key="conv.id"
                @click="$emit('select-conversation', conv.id)"
                :class="[
                    'p-4 border-b border-gray-100 dark:border-gray-700 cursor-pointer transition-all duration-150',
                    currentConversationId === conv.id 
                        ? 'bg-gradient-to-r from-blue-50 to-white dark:from-blue-900/30 dark:to-gray-800 border-l-4 border-l-blue-500 dark:border-l-blue-400 shadow-sm' 
                        : 'hover:bg-white dark:hover:bg-gray-700 hover:shadow-sm',
                ]"
            >
                <div class="flex items-start space-x-3">
                    <!-- Avatar -->
                    <div class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-sm font-semibold">
                        {{ getInitials(getConversationTitle(conv)) }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {{ getConversationTitle(conv) }}
                            </h3>
                            <span
                                v-if="conv.unread_count > 0"
                                class="flex-shrink-0 ml-2 bg-blue-600 dark:bg-blue-500 text-white text-xs font-bold rounded-full w-5 h-5 flex items-center justify-center"
                            >
                                {{ conv.unread_count > 99 ? '99+' : conv.unread_count }}
                            </span>
                        </div>
                        <p v-if="conv.latest_message" class="text-xs text-gray-500 dark:text-gray-400 mt-1 truncate">
                            <span class="font-medium">{{ conv.latest_message.user.name }}:</span>
                            {{ conv.latest_message.body }}
                        </p>
                        <p v-else class="text-xs text-gray-400 dark:text-gray-500 mt-1">No messages yet</p>
                        <div class="mt-1 flex items-center justify-between">
                            <span class="text-xs text-gray-400 dark:text-gray-500">
                                {{ conv.latest_message ? formatTime(conv.latest_message.created_at) : formatTime(conv.updated_at) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div v-if="conversations.length === 0" class="p-4 text-center text-gray-500 dark:text-gray-400 text-sm">
                <p class="mb-2">No conversations yet</p>
                <button
                    @click="showNewConversationModal = true"
                    class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 text-sm font-medium underline"
                >
                    Start a conversation
                </button>
            </div>
        </div>

        <!-- New Conversation Modal -->
        <div
            v-if="showNewConversationModal"
            class="fixed inset-0 bg-black bg-opacity-50 dark:bg-opacity-70 flex items-center justify-center z-50"
            @click.self="showNewConversationModal = false"
        >
            <div class="bg-white dark:bg-gray-800 rounded-lg p-6 w-full max-w-md mx-4 transition-colors duration-200">
                <h3 class="text-lg font-semibold mb-4 text-gray-900 dark:text-white">Start New Conversation</h3>
                <form @submit.prevent="createConversation">
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Select users to chat with:
                        </label>
                        <div class="max-h-48 overflow-y-auto border border-gray-300 dark:border-gray-600 rounded-md p-2 bg-white dark:bg-gray-700">
                            <label
                                v-for="user in availableUsers"
                                :key="user.id"
                                class="flex items-center p-2 hover:bg-gray-50 dark:hover:bg-gray-600 cursor-pointer rounded"
                            >
                                <input
                                    type="checkbox"
                                    :value="user.id"
                                    v-model="selectedUserIds"
                                    class="mr-2"
                                />
                                <span class="text-sm text-gray-900 dark:text-gray-100">{{ user.name }}</span>
                                <span class="text-xs text-gray-500 dark:text-gray-400 ml-2">({{ user.email }})</span>
                            </label>
                        </div>
                        <div v-if="form.errors.user_ids" class="mt-1 text-sm text-red-600 dark:text-red-400">
                            {{ form.errors.user_ids }}
                        </div>
                    </div>

                    <div class="mb-4" v-if="selectedUserIds.length > 1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Group Name (optional):
                        </label>
                        <input
                            type="text"
                            v-model="form.title"
                            placeholder="Enter group name"
                            class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-blue-500 focus:border-blue-500 bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-400"
                        />
                    </div>

                    <div class="flex justify-end space-x-2">
                        <button
                            type="button"
                            @click="showNewConversationModal = false"
                            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors duration-150"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            :disabled="form.processing || selectedUserIds.length === 0"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 dark:bg-blue-500 rounded-md hover:bg-blue-700 dark:hover:bg-blue-600 disabled:opacity-50 transition-colors duration-150"
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
import { ref, reactive, computed } from 'vue';
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

const emit = defineEmits(['select-conversation']);

const showNewConversationModal = ref(false);
const selectedUserIds = ref([]);
const searchQuery = ref('');

const filteredConversations = computed(() => {
    if (!searchQuery.value.trim()) {
        return props.conversations;
    }
    const query = searchQuery.value.toLowerCase();
    return props.conversations.filter((conv) => {
        const title = getConversationTitle(conv).toLowerCase();
        const latestMessage = conv.latest_message?.body?.toLowerCase() || '';
        return title.includes(query) || latestMessage.includes(query);
    });
});

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
        return conv.participants[1].name;
    }
    return 'Untitled Conversation';
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    
    try {
        // Parse the date string - Date.parse handles ISO 8601 UTC format correctly
        const dateUTC = Date.parse(dateString);
        
        // Check if date is valid
        if (isNaN(dateUTC)) {
            return '';
        }
        
        const nowUTC = Date.now();
        const diffMs = nowUTC - dateUTC;
        
        // Use absolute value to handle timezone issues or clock skew
        // If date is in future (negative diff), still calculate time difference
        const absDiffMs = Math.abs(diffMs);
        
        // Calculate differences
        const diffMins = Math.floor(absDiffMs / 60000);
        const diffHours = Math.floor(absDiffMs / 3600000);
        const diffDays = Math.floor(absDiffMs / 86400000);

        // Return formatted time
        if (diffMins < 1) return 'Just now';
        if (diffMins < 60) return `${diffMins}m ago`;
        if (diffHours < 24) return `${diffHours}h ago`;
        if (diffDays < 7) return `${diffDays}d ago`;
        
        // For older dates, show formatted date in Egypt timezone
        const date = new Date(dateUTC);
        return date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric',
            year: diffDays >= 365 ? 'numeric' : undefined,
            timeZone: 'Africa/Cairo'
        });
    } catch (error) {
        console.error('Error formatting time:', error, dateString);
        return '';
    }
};

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};
</script>

