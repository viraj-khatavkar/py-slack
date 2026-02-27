<script setup lang="ts">
import type { User } from '@/types/app/Models/User';
import type { PaginatedResponse } from '@/types/pagination';
import Spinner from '@/components/ui/Spinner.vue';
import { MagnifyingGlassIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    users: PaginatedResponse<User>;
    filters: {
        q: string | null;
    };
}>();

const allUsers = ref<User[]>([...props.users.data]);
const nextPageUrl = ref<string | null>(props.users.next_page_url);
const loadingMore = ref(false);
const searchQuery = ref(props.filters.q ?? '');

function handleSearch(): void {
    router.get('/users', searchQuery.value ? { q: searchQuery.value } : {}, { preserveState: false });
}

function loadMore(): void {
    if (!nextPageUrl.value || loadingMore.value) {
        return;
    }

    loadingMore.value = true;
    router.get(
        nextPageUrl.value,
        {},
        {
            preserveState: true,
            preserveScroll: true,
            only: ['users'],
            onSuccess: (page) => {
                const newUsers = (page.props.users as PaginatedResponse<User>);
                allUsers.value.push(...newUsers.data);
                nextPageUrl.value = newUsers.next_page_url;
                loadingMore.value = false;
            },
        },
    );
}
</script>

<template>
    <Head title="Users" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Users</h1>
            <p class="mt-1 text-sm text-gray-500">{{ users.total.toLocaleString() }} members</p>
        </div>

        <form class="mb-6" @submit.prevent="handleSearch">
            <div class="relative">
                <MagnifyingGlassIcon class="pointer-events-none absolute inset-y-0 left-3 h-full w-5 text-gray-400" />
                <input
                    v-model="searchQuery"
                    type="search"
                    placeholder="Search users..."
                    class="block w-full rounded-md border-gray-300 py-2 pr-3 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
        </form>

        <div v-if="allUsers.length === 0" class="rounded-lg border border-gray-200 bg-white py-16 text-center">
            <UsersIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No users found</h3>
            <p class="mt-1 text-sm text-gray-500">Try a different search term.</p>
        </div>

        <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <Link
                v-for="user in allUsers"
                :key="user.id"
                :href="`/users/${user.id}`"
                class="flex items-center gap-3 rounded-lg border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-gray-200 hover:bg-gray-50"
            >
                <div class="shrink-0">
                    <img
                        v-if="user.image_url"
                        :src="user.image_url"
                        :alt="user.name"
                        class="h-10 w-10 rounded-md"
                    />
                    <div
                        v-else
                        class="flex h-10 w-10 items-center justify-center rounded-md bg-indigo-100 text-sm font-medium text-indigo-600"
                    >
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold text-gray-900">{{ user.name }}</p>
                    <p v-if="user.title" class="truncate text-xs text-gray-500">{{ user.title }}</p>
                    <p class="text-xs text-gray-400">
                        {{ (user.messages_count ?? 0).toLocaleString() }} messages
                    </p>
                </div>
            </Link>
        </div>

        <div v-if="nextPageUrl" class="mt-6 flex justify-center">
            <button
                class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:opacity-50"
                :disabled="loadingMore"
                @click="loadMore"
            >
                <Spinner v-if="loadingMore" class="h-4 w-4" />
                Load More
            </button>
        </div>
    </div>
</template>
