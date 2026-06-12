<script setup lang="ts">
import {
    ChatBubbleLeftRightIcon,
    ClockIcon,
    ShieldCheckIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import MessageCard from '@/components/MessageCard.vue';
import ThreadPanel from '@/components/ThreadPanel.vue';
import Avatar from '@/components/ui/Avatar.vue';
import Spinner from '@/components/ui/Spinner.vue';
import { useThreadPanel } from '@/composables/useThreadPanel';
import type { Channel } from '@/types/app/Models/Channel';
import type { Message } from '@/types/app/Models/Message';
import type { User } from '@/types/app/Models/User';
import type { PaginatedResponse } from '@/types/pagination';

const props = defineProps<{
    user: User;
    messages: PaginatedResponse<Message>;
    filters: {
        channel_id: string | null;
        from_date: string | null;
        to_date: string | null;
    };
}>();

const {
    open: threadOpen,
    message: threadMessage,
    highlightId,
    openThread,
    closeThread,
} = useThreadPanel();

const channels = computed(() => usePage().props.channels as Channel[]);

const channelId = ref(props.filters.channel_id ?? '');
const fromDate = ref(props.filters.from_date ?? '');
const toDate = ref(props.filters.to_date ?? '');

const hasFilters = computed(() =>
    Boolean(channelId.value || fromDate.value || toDate.value),
);

function applyFilters(): void {
    const params: Record<string, string> = {};

    if (channelId.value) {
        params.channel_id = channelId.value;
    }
    if (fromDate.value) {
        params.from_date = fromDate.value;
    }
    if (toDate.value) {
        params.to_date = toDate.value;
    }

    router.get(`/users/${props.user.id}`, params, {
        preserveState: false,
        preserveScroll: true,
    });
}

function clearFilters(): void {
    channelId.value = '';
    fromDate.value = '';
    toDate.value = '';
    applyFilters();
}
</script>

<template>
    <Head :title="user.name" />

    <div>
        <!-- Profile header -->
        <div
            class="mb-6 rounded-lg border border-gray-100 bg-white p-6 shadow-sm"
        >
            <div class="flex items-start gap-4">
                <div class="shrink-0">
                    <Avatar
                        :name="user.name"
                        :image-url="user.image_url"
                        size-class="h-16 w-16"
                        rounded-class="rounded-lg"
                        text-class="text-xl"
                    />
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">
                            {{ user.name }}
                        </h1>
                        <span
                            v-if="user.is_admin"
                            class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                        >
                            <ShieldCheckIcon
                                class="h-3 w-3"
                                aria-hidden="true"
                            />
                            Admin
                        </span>
                        <span
                            v-if="user.is_deleted"
                            class="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-500"
                        >
                            Deactivated
                        </span>
                    </div>
                    <p v-if="user.title" class="mt-0.5 text-sm text-gray-600">
                        {{ user.title }}
                    </p>
                    <div
                        class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500"
                    >
                        <span
                            v-if="user.timezone_label"
                            class="inline-flex items-center gap-1"
                        >
                            <ClockIcon class="h-4 w-4" aria-hidden="true" />
                            {{ user.timezone_label }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <ChatBubbleLeftRightIcon
                                class="h-4 w-4"
                                aria-hidden="true"
                            />
                            {{ (user.messages_count ?? 0).toLocaleString() }}
                            messages
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h2 class="text-lg font-semibold text-gray-900">
                Messages
                <span
                    v-if="hasFilters"
                    class="text-sm font-normal text-gray-500"
                >
                    — {{ messages.total.toLocaleString() }} matching
                </span>
            </h2>

            <div class="flex flex-wrap items-center gap-2">
                <select
                    v-model="channelId"
                    aria-label="Filter by channel"
                    class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="applyFilters"
                >
                    <option value="">All channels</option>
                    <option
                        v-for="channel in channels"
                        :key="channel.id"
                        :value="String(channel.id)"
                    >
                        #{{ channel.name }}
                    </option>
                </select>
                <input
                    v-model="fromDate"
                    type="date"
                    aria-label="From date"
                    class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="applyFilters"
                />
                <span class="text-xs text-gray-400">to</span>
                <input
                    v-model="toDate"
                    type="date"
                    aria-label="To date"
                    class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    @change="applyFilters"
                />
                <button
                    v-if="hasFilters"
                    class="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-gray-500 hover:text-gray-700"
                    @click="clearFilters"
                >
                    <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    Clear
                </button>
            </div>
        </div>

        <div
            v-if="messages.data.length === 0"
            class="rounded-lg border border-gray-200 bg-white py-16 text-center"
        >
            <ChatBubbleLeftRightIcon
                class="mx-auto h-12 w-12 text-gray-300"
                aria-hidden="true"
            />
            <h3 class="mt-3 text-sm font-semibold text-gray-900">
                No messages
            </h3>
            <p class="mt-1 text-sm text-gray-500">
                {{
                    hasFilters
                        ? 'No messages match your filters.'
                        : "This user hasn't posted any messages."
                }}
            </p>
        </div>

        <InfiniteScroll
            v-else
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
    </div>

    <ThreadPanel
        :open="threadOpen"
        :message="threadMessage"
        :highlight-id="highlightId"
        @close="closeThread"
    />
</template>
