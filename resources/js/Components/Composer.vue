<template>
    <div class="p-4 border-t border-gray-200 dark:border-gray-700 bg-gradient-to-r from-white to-gray-50 dark:from-gray-800 dark:to-gray-900 shadow-lg transition-colors duration-200">
        <!-- Image Previews -->
        <div v-if="selectedImages.length > 0" class="mb-3 flex flex-wrap gap-2">
            <div
                v-for="(image, index) in selectedImages"
                :key="index"
                class="relative group"
            >
                <img
                    :src="image.preview"
                    alt="Preview"
                    class="w-20 h-20 object-cover rounded-lg border border-gray-300"
                />
                <button
                    @click="removeImage(index)"
                    class="absolute -top-2 -right-2 bg-red-500 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs hover:bg-red-600 transition-colors duration-150"
                >
                    ×
                </button>
            </div>
        </div>

        <form @submit.prevent="sendMessage" class="flex items-end space-x-3">
            <!-- Image Upload Button -->
            <label class="flex-shrink-0 cursor-pointer">
                <input
                    ref="fileInput"
                    type="file"
                    accept="image/jpeg,image/png,image/webp"
                    multiple
                    @change="handleFileSelect"
                    class="hidden"
                />
                <div class="p-3 text-gray-600 dark:text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors duration-150">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                </div>
            </label>

            <div class="flex-1 relative">
                <textarea
                    ref="textareaRef"
                    v-model="messageBody"
                    @keydown.enter.exact.prevent="sendMessage"
                    @keydown.enter.shift.exact="handleShiftEnter"
                    @input="handleInput"
                    @keydown="handleTyping"
                    :style="{ height: textareaHeight + 'px', minHeight: '44px', maxHeight: '120px' }"
                    class="w-full px-4 py-3 pr-12 border-2 border-blue-500 dark:border-blue-400 rounded-xl focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-blue-500 dark:focus:border-blue-400 resize-none overflow-hidden transition-all shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-400"
                    placeholder="Type a message..."
                    @focus="handleFocus"
                ></textarea>
            </div>
            <button
                type="submit"
                :disabled="(!messageBody.trim() && selectedImages.length === 0) || sending"
                class="flex-shrink-0 px-6 py-3 bg-gradient-to-r from-blue-500 to-blue-600 dark:from-blue-600 dark:to-blue-700 text-white rounded-lg hover:from-blue-600 hover:to-blue-700 dark:hover:from-blue-700 dark:hover:to-blue-800 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md hover:shadow-lg font-medium"
            >
                <span v-if="sending" class="flex items-center">
                    <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    Sending...
                </span>
                <span v-else>Send</span>
            </button>
        </form>
    </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
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
const textareaRef = ref(null);
const fileInput = ref(null);
const textareaHeight = ref(44);
const selectedImages = ref([]);
let typingTimeout = null;
let echoChannel = null;

// Initialize Echo channel for typing indicators
onMounted(() => {
    if (window.Echo && props.conversationId) {
        echoChannel = window.Echo.private(`conversation.${props.conversationId}`);
        
        echoChannel.subscribed(() => {
            console.log('✅ Composer subscribed to conversation channel for typing');
        });
    }
});

onUnmounted(() => {
    if (typingTimeout) {
        clearTimeout(typingTimeout);
    }
});

const stopTypingIndicator = () => {
    if (!echoChannel) {
        return;
    }
    
    // Clear timeout if exists
    if (typingTimeout) {
        clearTimeout(typingTimeout);
        typingTimeout = null;
    }
    
    // Notify others that typing has stopped
    const user = window.Laravel?.user || { id: null, name: 'Someone' };
    try {
        echoChannel.whisper('typing', {
            user: {
                id: user.id,
                name: user.name,
            },
            stopped: true,
        });
    } catch (error) {
        console.error('Error stopping typing indicator:', error);
    }
};

const adjustTextareaHeight = () => {
    if (textareaRef.value) {
        // Reset height to auto to get the correct scrollHeight
        textareaRef.value.style.height = 'auto';
        const scrollHeight = textareaRef.value.scrollHeight;
        // Set height based on content, with min and max limits
        textareaHeight.value = Math.min(Math.max(scrollHeight, 44), 120);
    }
};

const handleInput = () => {
    adjustTextareaHeight();
    // Don't trigger typing on every input, only on keydown
};

const handleShiftEnter = () => {
    messageBody.value += '\n';
    adjustTextareaHeight();
};

const handleFocus = () => {
    // Prevent page scroll when focusing textarea
    if (textareaRef.value) {
        textareaRef.value.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
};

const handleTyping = () => {
    if (!echoChannel || !props.conversationId) {
        return;
    }
    
    // Get user info from Inertia shared props (available via window)
    const user = window.Laravel?.user || { id: null, name: 'Someone' };
    
    try {
        // Clear previous timeout to prevent auto-stop
        if (typingTimeout) {
            clearTimeout(typingTimeout);
            typingTimeout = null;
        }
        
        // Send typing indicator
        echoChannel.whisper('typing', {
            user: {
                id: user.id,
                name: user.name,
            },
        });
        
        // Don't auto-stop typing indicator - it will only stop when message is sent
    } catch (error) {
        console.error('Error sending typing indicator:', error);
    }
};

const handleFileSelect = (event) => {
    const files = Array.from(event.target.files);
    const maxFiles = 5;
    const maxSize = 5 * 1024 * 1024; // 5MB
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];

    files.forEach((file) => {
        if (selectedImages.value.length >= maxFiles) {
            alert(`Maximum ${maxFiles} images allowed`);
            return;
        }

        if (file.size > maxSize) {
            alert(`File ${file.name} is too large. Maximum size is 5MB.`);
            return;
        }

        if (!allowedTypes.includes(file.type)) {
            alert(`File ${file.name} is not a supported image type. Please use JPEG, PNG, or WebP.`);
            return;
        }

        const reader = new FileReader();
        reader.onload = (e) => {
            selectedImages.value.push({
                file: file,
                preview: e.target.result,
            });
        };
        reader.readAsDataURL(file);
    });

    // Reset file input
    if (fileInput.value) {
        fileInput.value.value = '';
    }
};

const removeImage = (index) => {
    selectedImages.value.splice(index, 1);
};

const sendMessage = async () => {
    if ((!messageBody.value.trim() && selectedImages.value.length === 0) || sending.value) {
        return;
    }

    // Clear typing indicator immediately when sending
    stopTypingIndicator();

    const body = messageBody.value.trim();
    const images = [...selectedImages.value];
    
    // Clear form
    messageBody.value = '';
    selectedImages.value = [];
    textareaHeight.value = 44;
    sending.value = true;

    try {
        const formData = new FormData();
        if (body) {
            formData.append('body', body);
        }
        formData.append('type', images.length > 0 ? 'image' : 'text');
        
        images.forEach((img) => {
            formData.append('files[]', img.file);
        });

        const response = await axios.post(`/conversations/${props.conversationId}/messages`, formData, {
            headers: {
                'Content-Type': 'multipart/form-data',
            },
        });

        emit('message-sent', response.data.message);
    } catch (error) {
        console.error('Error sending message:', error);
        // Restore form on error
        messageBody.value = body;
        selectedImages.value = images;
        alert('Failed to send message. Please try again.');
    } finally {
        sending.value = false;
    }
};
</script>

