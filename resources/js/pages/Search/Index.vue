<script setup lang="ts">
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { Head, InfiniteScroll, router } from '@inertiajs/vue3';
import MessageCard from '@/components/MessageCard.vue';
import SearchForm from '@/components/SearchForm.vue';
import ThreadPanel from '@/components/ThreadPanel.vue';
import Spinner from '@/components/ui/Spinner.vue';
import { useThreadPanel } from '@/composables/useThreadPanel';
import type { Message } from '@/types/app/Models/Message';
import type { PaginatedResponse } from '@/types/pagination';

defineProps<{
    messages: PaginatedResponse<Message> | null;
    filters: {
        q: string | null;
        from_date: string | null;
        to_date: string | null;
        channel_id: string | null;
        user_id: string | null;
        sort_by: string;
        sort_direction: string;
    };
    filterUser?: { id: number; name: string; image_url: string | null } | null;
}>();

const {
    open: threadOpen,
    message: threadMessage,
    highlightId,
    openThread,
    closeThread,
} = useThreadPanel();

function handleSearch(params: Record<string, string>): void {
    router.get('/search', params, {
        preserveState: false,
    });
}
</script>

<template>
    <Head title="Search" />

    <div>
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Search Messages</h1>

        <SearchForm
            :filters="filters"
            :filter-user="filterUser"
            @search="handleSearch"
        />

        <div class="mt-6">
            <!-- Initial state: no search performed -->
            <div
                v-if="!messages && !filters.q"
                class="rounded-lg border border-gray-200 bg-white py-16 text-center"
            >
                <MagnifyingGlassIcon
                    class="mx-auto h-12 w-12 text-gray-300"
                    aria-hidden="true"
                />
                <h3 class="mt-3 text-sm font-semibold text-gray-900">
                    Search the archive
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    Enter a query above to search through all messages.
                </p>
            </div>

            <!-- No results -->
            <div
                v-else-if="!messages || messages.data.length === 0"
                class="rounded-lg border border-gray-200 bg-white py-16 text-center"
            >
                <MagnifyingGlassIcon
                    class="mx-auto h-12 w-12 text-gray-300"
                    aria-hidden="true"
                />
                <h3 class="mt-3 text-sm font-semibold text-gray-900">
                    No results found
                </h3>
                <p class="mt-1 text-sm text-gray-500">
                    No messages match "{{ filters.q }}". Try a different search
                    term or adjust your filters.
                </p>
            </div>

            <!-- Results -->
            <template v-else>
                <p class="mb-3 text-sm text-gray-500">
                    {{ messages.total.toLocaleString() }}
                    {{ messages.total === 1 ? 'result' : 'results' }} for "{{
                        filters.q
                    }}"
                    <span v-if="filterUser">from {{ filterUser.name }}</span>
                </p>

                <InfiniteScroll
                    data="messages"
                    :buffer="400"
                    :manual-after="15"
                    class="space-y-3"
                >
                    <MessageCard
                        v-for="message in messages.data"
                        :key="message.id"
                        :message="message"
                        :show-channel="true"
                        :highlight="filters.q"
                        @open-thread="openThread"
                    />

                    <template #next="{ loading, fetch, hasMore, manualMode }">
                        <div v-if="hasMore" class="flex justify-center pt-2">
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
            </template>
        </div>
    </div>

    <ThreadPanel
        :open="threadOpen"
        :message="threadMessage"
        :highlight-id="highlightId"
        @close="closeThread"
    />
</template>
