<script setup lang="ts">
import type { Channel } from '@/types/app/Models/Channel';
import type { Message } from '@/types/app/Models/Message';
import type { PaginatedResponse } from '@/types/pagination';
import MessageCard from '@/components/MessageCard.vue';
import ThreadPanel from '@/components/ThreadPanel.vue';
import Spinner from '@/components/ui/Spinner.vue';
import {
    ArrowDownIcon,
    ArrowUpIcon,
    ChatBubbleLeftRightIcon,
    FunnelIcon,
    MapPinIcon,
    UserGroupIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    channel: Channel;
    messages: PaginatedResponse<Message>;
    sortDirection: string;
    filters: {
        date: string | null;
        pinned: boolean;
    };
    dateRange?: {
        min: string | null;
        max: string | null;
    };
}>();

const allMessages = ref<Message[]>([...props.messages.data]);
const nextPageUrl = ref<string | null>(props.messages.next_page_url);
const loadingMore = ref(false);

const threadOpen = ref(false);
const threadMessage = ref<Message | null>(null);

const hasFilters = computed(() => props.filters.date || props.filters.pinned);

function toggleSort(): void {
    const newDirection = props.sortDirection === 'desc' ? 'asc' : 'desc';
    navigate({ sort_direction: newDirection });
}

function navigate(params: Record<string, string | boolean | null>): void {
    const query: Record<string, string | boolean | null> = {
        sort_direction: props.sortDirection,
        date: props.filters.date,
        pinned: props.filters.pinned || null,
        ...params,
    };

    // Remove null/false values
    const cleaned: Record<string, string> = {};
    for (const [key, value] of Object.entries(query)) {
        if (value !== null && value !== false && value !== '') {
            cleaned[key] = String(value);
        }
    }

    router.get(`/channels/${props.channel.name}`, cleaned, { preserveState: false });
}

function setDate(event: Event): void {
    const target = event.target as HTMLInputElement;
    navigate({ date: target.value || null });
}

function togglePinned(): void {
    navigate({ pinned: props.filters.pinned ? null : true });
}

function clearFilters(): void {
    router.get(`/channels/${props.channel.name}`, { sort_direction: props.sortDirection }, { preserveState: false });
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
                const newMessages = (page.props.messages as PaginatedResponse<Message>);
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
    <Head :title="`#${channel.name}`" />

    <div>
        <div class="mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">#{{ channel.name }}</h1>
                    <div class="mt-1 flex items-center gap-3">
                        <p v-if="messages.total > 0" class="text-sm text-gray-500">
                            {{ messages.total.toLocaleString() }} {{ messages.total === 1 ? 'message' : 'messages' }}
                        </p>
                        <span v-if="channel.member_count" class="inline-flex items-center gap-1 text-sm text-gray-500">
                            <UserGroupIcon class="h-4 w-4" />
                            {{ channel.member_count.toLocaleString() }} members
                        </span>
                    </div>
                </div>
                <button
                    class="inline-flex items-center gap-1.5 rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                    @click="toggleSort"
                >
                    <component :is="sortDirection === 'desc' ? ArrowDownIcon : ArrowUpIcon" class="h-4 w-4" />
                    {{ sortDirection === 'desc' ? 'Newest First' : 'Oldest First' }}
                </button>
            </div>

            <p v-if="channel.topic" class="mt-2 text-sm text-gray-600">{{ channel.topic }}</p>
            <p v-if="channel.purpose" class="mt-1 text-sm text-gray-500 italic">{{ channel.purpose }}</p>

            <!-- Filters -->
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <FunnelIcon class="h-4 w-4 text-gray-400" />
                    <input
                        type="date"
                        :value="filters.date ?? ''"
                        :min="dateRange?.min?.split(' ')[0]"
                        :max="dateRange?.max?.split(' ')[0]"
                        class="rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="setDate"
                    />
                </div>

                <button
                    class="inline-flex items-center gap-1.5 rounded-md px-3 py-1.5 text-sm font-medium shadow-sm ring-1 ring-inset"
                    :class="
                        filters.pinned
                            ? 'bg-amber-50 text-amber-700 ring-amber-300'
                            : 'bg-white text-gray-700 ring-gray-300 hover:bg-gray-50'
                    "
                    @click="togglePinned"
                >
                    <MapPinIcon class="h-4 w-4" />
                    Pinned
                </button>

                <button
                    v-if="hasFilters"
                    class="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-gray-500 hover:text-gray-700"
                    @click="clearFilters"
                >
                    <XMarkIcon class="h-4 w-4" />
                    Clear filters
                </button>
            </div>
        </div>

        <div v-if="allMessages.length === 0" class="rounded-lg border border-gray-200 bg-white py-16 text-center">
            <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No messages</h3>
            <p class="mt-1 text-sm text-gray-500">
                {{ hasFilters ? 'No messages match your filters.' : "This channel doesn't have any messages yet." }}
            </p>
        </div>

        <div v-else class="space-y-3">
            <MessageCard
                v-for="message in allMessages"
                :key="message.id"
                :message="message"
                @open-thread="openThread"
            />
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

    <ThreadPanel :open="threadOpen" :message="threadMessage" @close="threadOpen = false" />
</template>
