<script setup lang="ts">
import type { Message } from '@/types/app/Models/Message';
import type { PaginatedResponse } from '@/types/pagination';
import MessageCard from '@/components/MessageCard.vue';
import SearchForm from '@/components/SearchForm.vue';
import ThreadPanel from '@/components/ThreadPanel.vue';
import Spinner from '@/components/ui/Spinner.vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const props = defineProps<{
    messages: PaginatedResponse<Message> | null;
    filters: {
        q: string | null;
        from_date: string | null;
        to_date: string | null;
        channel_id: string | null;
        sort_by: string;
        sort_direction: string;
    };
}>();

const allMessages = ref<Message[]>(props.messages?.data ?? []);
const nextPageUrl = ref<string | null>(props.messages?.next_page_url ?? null);
const loadingMore = ref(false);

const threadOpen = ref(false);
const threadMessage = ref<Message | null>(null);

watch(
    () => props.messages,
    (newMessages) => {
        allMessages.value = newMessages?.data ?? [];
        nextPageUrl.value = newMessages?.next_page_url ?? null;
    },
);

function handleSearch(params: Record<string, string>): void {
    router.get('/search', params, {
        preserveState: false,
    });
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
            only: ['messages'],
            onSuccess: (page) => {
                const newMessages = page.props.messages as PaginatedResponse<Message>;
                allMessages.value.push(...newMessages.data);
                nextPageUrl.value = newMessages.next_page_url;
                loadingMore.value = false;
            },
        },
    );
}

function openThread(message: Message): void {
    threadMessage.value = message;
    threadOpen.value = true;
}
</script>

<template>
    <Head title="Search" />

    <div>
        <h1 class="mb-6 text-2xl font-bold text-gray-900">Search Messages</h1>

        <SearchForm :filters="filters" @search="handleSearch" />

        <div class="mt-6">
            <!-- Initial state: no search performed -->
            <div v-if="!messages && !filters.q" class="rounded-lg border border-gray-200 bg-white py-16 text-center">
                <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-3 text-sm font-semibold text-gray-900">Search the archive</h3>
                <p class="mt-1 text-sm text-gray-500">Enter a query above to search through all messages.</p>
            </div>

            <!-- No results -->
            <div v-else-if="allMessages.length === 0" class="rounded-lg border border-gray-200 bg-white py-16 text-center">
                <MagnifyingGlassIcon class="mx-auto h-12 w-12 text-gray-300" />
                <h3 class="mt-3 text-sm font-semibold text-gray-900">No results found</h3>
                <p class="mt-1 text-sm text-gray-500">
                    No messages match "{{ filters.q }}". Try a different search term or adjust your filters.
                </p>
            </div>

            <!-- Results -->
            <template v-else>
                <p class="mb-3 text-sm text-gray-500">
                    {{ messages?.total.toLocaleString() }} {{ messages?.total === 1 ? 'result' : 'results' }}
                    for "{{ filters.q }}"
                </p>

                <div class="space-y-3">
                    <MessageCard
                        v-for="message in allMessages"
                        :key="message.id"
                        :message="message"
                        :show-channel="true"
                        @open-thread="openThread"
                    />
                </div>
            </template>

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
    </div>

    <ThreadPanel :open="threadOpen" :message="threadMessage" @close="threadOpen = false" />
</template>
