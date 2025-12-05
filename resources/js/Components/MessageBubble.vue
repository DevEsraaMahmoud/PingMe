<template>
    <div :class="['flex', isOwn ? 'justify-end' : 'justify-start']">
        <div
            :class="[
                'max-w-xs lg:max-w-md px-4 py-2 rounded-lg',
                isOwn
                    ? 'bg-blue-600 text-white rounded-br-none'
                    : 'bg-gray-200 text-gray-800 rounded-bl-none',
            ]"
        >
            <div v-if="!isOwn" class="text-xs font-semibold mb-1 text-gray-600">
                {{ message.user.name }}
            </div>
            <div class="text-sm whitespace-pre-wrap">{{ message.body }}</div>
            <div
                :class="[
                    'text-xs mt-1',
                    isOwn ? 'text-blue-100' : 'text-gray-500',
                ]"
            >
                {{ formatTime(message.created_at) }}
                <span v-if="message.edited_at" class="ml-1">(edited)</span>
            </div>
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

const formatTime = (dateString) => {
    if (!dateString) return '';
    const date = new Date(dateString);
    return date.toLocaleTimeString('en-US', {
        hour: 'numeric',
        minute: '2-digit',
    });
};
</script>

