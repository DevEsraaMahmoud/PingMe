<template>
    <div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    Create your account
                </h2>
            </div>
            <form class="mt-8 space-y-6" @submit.prevent="submit">
                <div class="rounded-md shadow-sm space-y-4">
                    <div>
                        <label for="name" class="sr-only">Name</label>
                        <input
                            id="name"
                            v-model="form.name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Full name"
                        />
                        <div v-if="form.errors.name || errors.name" class="mt-1 text-sm text-red-600">
                            {{ form.errors.name || errors.name }}
                        </div>
                    </div>
                    <div>
                        <label for="email" class="sr-only">Email address</label>
                        <input
                            id="email"
                            v-model="form.email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Email address"
                        />
                        <div v-if="form.errors.email || errors.email" class="mt-1 text-sm text-red-600">
                            {{ form.errors.email || errors.email }}
                        </div>
                    </div>
                    <div>
                        <label for="password" class="sr-only">Password</label>
                        <input
                            id="password"
                            v-model="form.password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Password"
                        />
                        <div v-if="form.errors.password || errors.password" class="mt-1 text-sm text-red-600">
                            {{ form.errors.password || errors.password }}
                        </div>
                    </div>
                    <div>
                        <label for="password_confirmation" class="sr-only">Confirm Password</label>
                        <input
                            id="password_confirmation"
                            v-model="form.password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm"
                            placeholder="Confirm password"
                        />
                    </div>
                </div>

                <div>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                    >
                        {{ form.processing ? 'Creating account...' : 'Register' }}
                    </button>
                </div>

                <div class="text-center">
                    <a href="/login" class="text-sm text-indigo-600 hover:text-indigo-500">
                        Already have an account? Sign in
                    </a>
                </div>
            </form>
        </div>
    </div>
</template>

<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    errors: {
        type: Object,
        default: () => ({}),
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register', {
        preserveScroll: true,
    });
};
</script>

