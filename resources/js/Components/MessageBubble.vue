<template>
    <div :class="['flex items-end space-x-3 mb-1', isOwn ? 'justify-end' : 'justify-start']">
        <!-- Avatar for other users -->
        <div v-if="!isOwn" class="flex-shrink-0 w-10 h-10 rounded-full bg-gradient-to-br from-blue-500 via-purple-500 to-pink-500 flex items-center justify-center text-white text-sm font-bold shadow-md">
            {{ getInitials(message.user.name) }}
        </div>
        
        <div class="flex flex-col" :class="isOwn ? 'items-end' : 'items-start'">
            <!-- <div v-if="!isOwn" class="text-xs font-semibold text-gray-700 mb-1.5 px-1">
                {{ message.user.name }}
            </div> -->
            <div
                    :class="[
                    'max-w-xs lg:max-w-md px-4 py-3 rounded-2xl shadow-md',
                    isOwn
                        ? 'bg-gradient-to-br from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 text-white rounded-br-md'
                        : 'bg-white dark:bg-gray-700 text-gray-800 dark:text-gray-100 border border-gray-200 dark:border-gray-600 rounded-bl-md',
                ]"
            >
                <!-- Images -->
                <div v-if="message.attachments && message.attachments.length > 0" class="mb-2 space-y-2">
                    <div
                        v-for="(attachment, index) in message.attachments"
                        :key="attachment.id || index"
                        class="relative group"
                    >
                        <img
                            :src="attachment.url"
                            :alt="message.body || 'Image'"
                            @click="openImageModal(attachment.url)"
                            class="max-w-full h-auto rounded-lg cursor-pointer hover:opacity-90 transition-opacity duration-150"
                            style="max-height: 150px; max-width: 200px"
                        />
                    </div>
                </div>
                
                <!-- Text Body -->
                <div v-if="message.body" class="text-sm whitespace-pre-wrap leading-relaxed">
                    {{ message.body }}
                </div>
                
                <!-- Timestamp -->
                <div
                    :class="[
                        'text-xs mt-1.5 flex items-center',
                        isOwn ? 'text-blue-100' : 'text-gray-400 dark:text-gray-500',
                    ]"
                >
                    {{ formatTime(message.created_at) }}
                    <span v-if="message.edited_at" class="ml-1 opacity-75">(edited)</span>
                </div>
            </div>
            
            <!-- Image Modal -->
            <div
                v-if="showImageModal"
                class="fixed inset-0 bg-black bg-opacity-90 flex items-center justify-center z-50 p-4"
                @click="showImageModal = false"
            >
                <div class="relative max-w-5xl max-h-full">
                    <button
                        @click="showImageModal = false"
                        class="absolute -top-12 right-0 text-white hover:text-gray-300 transition-colors duration-150"
                    >
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                    <img
                        :src="modalImageUrl"
                        alt="Full size"
                        class="max-w-full max-h-[90vh] object-contain rounded-xl shadow-2xl"
                        @click.stop
                    />
                </div>
            </div>
        </div>
        
        <!-- Avatar for own messages -->
        <div v-if="isOwn" class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-green-400 to-blue-500 flex items-center justify-center text-white text-xs font-semibold shadow-sm">
            {{ getInitials(message.user.name) }}
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    message: {
        type: Object,
        required: true,
    },
    isOwn: {
        type: Boolean,
        default: false,
    },
});

const showImageModal = ref(false);
const modalImageUrl = ref('');

const getInitials = (name) => {
    if (!name) return '?';
    const parts = name.trim().split(' ');
    if (parts.length >= 2) {
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    }
    return name.substring(0, 2).toUpperCase();
};

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
        timeZone: 'Africa/Cairo',
    });
};

const openImageModal = (url) => {
    modalImageUrl.value = url;
    showImageModal.value = true;
};
</script>

