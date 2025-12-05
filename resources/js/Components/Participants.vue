<template>
    <div class="flex items-center space-x-2">
        <div class="flex -space-x-2">
            <div
                v-for="(participant, index) in participants.slice(0, 3)"
                :key="participant.id"
                :class="[
                    'w-8 h-8 rounded-full bg-white bg-opacity-20 flex items-center justify-center text-xs font-semibold text-black border-2 border-white border-opacity-30',
                    index > 0 ? '-ml-2' : '',
                ]"
                :title="participant.name"
            >
                {{ getInitials(participant.name) }}
            </div>
        </div>
        <span v-if="participants.length > 3" class="text-sm text-blue-100">
            +{{ participants.length - 3 }} more
        </span>
    </div>
</template>

<script setup>
const props = defineProps({
    participants: {
        type: Array,
        default: () => [],
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
</script>

