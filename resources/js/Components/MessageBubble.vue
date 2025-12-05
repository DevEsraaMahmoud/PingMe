<template>
    <div :class="['flex items-end space-x-2', isOwn ? 'justify-end' : 'justify-start']">
        <!-- Avatar for other users -->
        <div v-if="!isOwn" class="flex-shrink-0 w-8 h-8 rounded-full bg-gradient-to-br from-blue-400 to-purple-500 flex items-center justify-center text-white text-xs font-semibold shadow-sm">
            {{ getInitials(message.user.name) }}
        </div>
        
        <div class="flex flex-col" :class="isOwn ? 'items-end' : 'items-start'">
            <div v-if="!isOwn" class="text-xs font-medium text-gray-600 mb-1 px-1">
                {{ message.user.name }}
            </div>
            <div
                :class="[
                    'max-w-xs lg:max-w-md px-4 py-2.5 rounded-2xl shadow-sm',
                    isOwn
                        ? 'bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-br-md'
                        : 'bg-white text-gray-800 rounded-bl-md border border-gray-100',
                ]"
            >
                <div class="text-sm whitespace-pre-wrap leading-relaxed">{{ message.body }}</div>
                <div
                    :class="[
                        'text-xs mt-1.5 flex items-center',
                        isOwn ? 'text-blue-100' : 'text-gray-400',
                    ]"
                >
                    {{ formatTime(message.created_at) }}
                    <span v-if="message.edited_at" class="ml-1 opacity-75">(edited)</span>
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
    });
};
</script>

