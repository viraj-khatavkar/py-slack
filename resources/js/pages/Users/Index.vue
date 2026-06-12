<script setup lang="ts">
import { MagnifyingGlassIcon, UsersIcon } from '@heroicons/vue/24/outline';
import { Head, InfiniteScroll, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Avatar from '@/components/ui/Avatar.vue';
import Spinner from '@/components/ui/Spinner.vue';
import type { User } from '@/types/app/Models/User';
import type { PaginatedResponse } from '@/types/pagination';

const props = defineProps<{
    users: PaginatedResponse<User>;
    filters: {
        q: string | null;
        sort: string;
    };
}>();

const searchQuery = ref(props.filters.q ?? '');
const sort = ref(props.filters.sort ?? 'active');
const gridElement = ref<HTMLElement | null>(null);

function applyFilters(): void {
    const params: Record<string, string> = {};

    if (searchQuery.value) {
        params.q = searchQuery.value;
    }
    if (sort.value !== 'active') {
        params.sort = sort.value;
    }

    router.get('/users', params, { preserveState: false });
}
</script>

<template>
    <Head title="Users" />

    <div>
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900">Users</h1>
            <p class="mt-1 text-sm text-gray-500">
                {{ users.total.toLocaleString() }} members
            </p>
        </div>

        <form
            class="mb-6 flex flex-wrap items-center gap-3"
            @submit.prevent="applyFilters"
        >
            <div class="relative min-w-48 flex-1">
                <MagnifyingGlassIcon
                    class="pointer-events-none absolute inset-y-0 left-3 h-full w-5 text-gray-400"
                    aria-hidden="true"
                />
                <input
                    v-model="searchQuery"
                    type="search"
                    aria-label="Search users"
                    placeholder="Search users..."
                    class="block w-full rounded-md border-gray-300 py-2 pr-3 pl-10 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                />
            </div>
            <select
                v-model="sort"
                aria-label="Sort users"
                class="rounded-md border-gray-300 py-2 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                @change="applyFilters"
            >
                <option value="active">Most active</option>
                <option value="name">Name (A–Z)</option>
            </select>
        </form>

        <div
            v-if="users.data.length === 0"
            class="rounded-lg border border-gray-200 bg-white py-16 text-center"
        >
            <UsersIcon
                class="mx-auto h-12 w-12 text-gray-300"
                aria-hidden="true"
            />
            <h3 class="mt-3 text-sm font-semibold text-gray-900">
                No users found
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                Try a different search term.
            </p>
        </div>

        <InfiniteScroll
            v-else
            data="users"
            :buffer="400"
            :manual-after="10"
            :items-element="() => gridElement"
        >
            <div
                ref="gridElement"
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="user in users.data"
                    :key="user.id"
                    :href="`/users/${user.id}`"
                    class="flex items-center gap-3 rounded-lg border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-gray-200 hover:bg-gray-50"
                >
                    <div class="shrink-0">
                        <Avatar
                            :name="user.name"
                            :image-url="user.image_url"
                            size-class="h-10 w-10"
                        />
                    </div>
                    <div class="min-w-0 flex-1">
                        <p
                            class="flex items-center gap-1.5 truncate text-sm font-semibold text-gray-900"
                        >
                            {{ user.name }}
                            <span
                                v-if="user.is_deleted"
                                class="rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500"
                            >
                                Deactivated
                            </span>
                        </p>
                        <p
                            v-if="user.title"
                            class="truncate text-xs text-gray-500"
                        >
                            {{ user.title }}
                        </p>
                        <p class="text-xs text-gray-400">
                            {{ (user.messages_count ?? 0).toLocaleString() }}
                            {{
                                (user.messages_count ?? 0) === 1
                                    ? 'message'
                                    : 'messages'
                            }}
                        </p>
                    </div>
                </Link>
            </div>

            <template #next="{ loading, fetch, hasMore, manualMode }">
                <div v-if="hasMore" class="flex justify-center pt-6">
                    <button
                        v-if="manualMode"
                        class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:opacity-50"
                        :disabled="loading"
                        @click="fetch"
                    >
                        <Spinner v-if="loading" class="h-4 w-4" />
                        Load More
                    </button>
                    <Spinner
                        v-else-if="loading"
                        class="h-5 w-5 text-gray-400"
                    />
                </div>
            </template>
        </InfiniteScroll>
    </div>
</template>
