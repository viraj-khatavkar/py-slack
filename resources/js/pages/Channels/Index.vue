<script setup lang="ts">
import {
    ArrowDownIcon,
    ArrowUpIcon,
    ChatBubbleLeftRightIcon,
    FunnelIcon,
    MapPinIcon,
    UserGroupIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Head, InfiniteScroll, router, usePage } from '@inertiajs/vue3';
import { computed, nextTick, onMounted, ref } from 'vue';
import MessageCard from '@/components/MessageCard.vue';
import ThreadPanel from '@/components/ThreadPanel.vue';
import Spinner from '@/components/ui/Spinner.vue';
import { useThreadPanel } from '@/composables/useThreadPanel';
import type { Channel } from '@/types/app/Models/Channel';
import type { Message } from '@/types/app/Models/Message';
import type { PaginatedResponse } from '@/types/pagination';
import { formatDayHeading } from '@/utils/formatDate';
import { decodeEntities } from '@/utils/slackContent';

const props = defineProps<{
    channel: Channel;
    messages: PaginatedResponse<Message>;
    sortDirection: string;
    goto?: string | null;
    gotoMessage?: number | null;
    filters: {
        date: string | null;
        pinned: boolean;
    };
    dateRange?: {
        min: string | null;
        max: string | null;
    };
}>();

const {
    open: threadOpen,
    message: threadMessage,
    highlightId,
    openThread,
    closeThread,
} = useThreadPanel();

const dateMode = ref<'jump' | 'on'>(props.filters.date ? 'on' : 'jump');
const dateValue = ref(props.filters.date ?? props.goto ?? '');

const hasFilters = computed(() =>
    Boolean(props.filters.date || props.filters.pinned),
);

type TimelineEntry =
    | { type: 'divider'; key: string; day: string; label: string }
    | { type: 'message'; key: number; message: Message };

const timeline = computed<TimelineEntry[]>(() => {
    const entries: TimelineEntry[] = [];
    let lastDay = '';

    for (const message of props.messages.data) {
        const day = message.slack_timestamp.split(' ')[0];

        if (day !== lastDay) {
            entries.push({
                type: 'divider',
                key: `day-${day}-${message.id}`,
                day,
                label: formatDayHeading(message.slack_timestamp),
            });
            lastDay = day;
        }

        entries.push({ type: 'message', key: message.id, message });
    }

    return entries;
});

const page = usePage();

const hasJumpedAway = computed(() => {
    const match = /[?&]page=(\d+)/.exec(page.url);

    return match !== null && Number(match[1]) > 1;
});

function backToTop(): void {
    navigate({});
}

const flashedMessageId = ref<number | null>(null);

function findGotoTarget(): {
    element: HTMLElement;
    block: ScrollLogicalPosition;
} | null {
    if (props.gotoMessage) {
        const element = document.getElementById(`message-${props.gotoMessage}`);

        return element ? { element, block: 'center' } : null;
    }

    if (!props.goto) {
        return null;
    }

    const targetDay =
        timeline.value.find(
            (entry) => entry.type === 'divider' && entry.day === props.goto,
        ) ??
        timeline.value.find(
            (entry) =>
                entry.type === 'divider' &&
                (props.sortDirection === 'desc'
                    ? entry.day <= props.goto!
                    : entry.day >= props.goto!),
        );

    const element =
        targetDay?.type === 'divider'
            ? document.getElementById(`day-${targetDay.day}`)
            : null;

    return element ? { element, block: 'start' } : null;
}

const STICKY_OFFSET = 72;

function scrollToGotoTarget(attempt = 0): void {
    const target = findGotoTarget();

    if (target) {
        const { top } = target.element.getBoundingClientRect();
        const expected =
            target.block === 'center' ? window.innerHeight / 2 : STICKY_OFFSET;
        const tolerance =
            target.block === 'center' ? window.innerHeight / 4 : 40;

        if (Math.abs(top - expected) > tolerance) {
            target.element.scrollIntoView({ block: target.block });
        }
    }

    if (attempt < 4) {
        setTimeout(() => scrollToGotoTarget(attempt + 1), 350);
    }
}

onMounted(() => {
    if (props.goto || props.gotoMessage) {
        void nextTick(() => scrollToGotoTarget());

        if (props.gotoMessage) {
            flashedMessageId.value = props.gotoMessage;
            setTimeout(() => (flashedMessageId.value = null), 3000);
        }
    }
});

const newerLabel = computed(() =>
    props.sortDirection === 'desc'
        ? 'Load newer messages'
        : 'Load earlier messages',
);
const olderLabel = computed(() =>
    props.sortDirection === 'desc'
        ? 'Load earlier messages'
        : 'Load newer messages',
);

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

    const cleaned: Record<string, string> = {};
    for (const [key, value] of Object.entries(query)) {
        if (value !== null && value !== false && value !== '') {
            cleaned[key] = String(value);
        }
    }

    router.get(`/channels/${props.channel.name}`, cleaned, {
        preserveState: false,
    });
}

function onDateChange(event: Event): void {
    const value = (event.target as HTMLInputElement).value;
    dateValue.value = value;

    if (!value) {
        navigate({ date: null });
        return;
    }

    if (dateMode.value === 'on') {
        navigate({ date: value });
    } else {
        navigate({ date: null, jump_date: value });
    }
}

function onDateModeChange(): void {
    if (dateValue.value) {
        if (dateMode.value === 'on') {
            navigate({ date: dateValue.value });
        } else {
            navigate({ date: null, jump_date: dateValue.value });
        }
    }
}

function togglePinned(): void {
    navigate({ pinned: props.filters.pinned ? null : true });
}

function clearFilters(): void {
    router.get(
        `/channels/${props.channel.name}`,
        { sort_direction: props.sortDirection },
        { preserveState: false },
    );
}
</script>

<template>
    <Head :title="`#${channel.name}`" />

    <div>
        <div class="mb-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">
                        #{{ channel.name }}
                    </h1>
                    <div
                        class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1"
                    >
                        <p v-if="hasFilters" class="text-sm text-gray-500">
                            {{ messages.total.toLocaleString() }} matching
                            {{ messages.total === 1 ? 'thread' : 'threads' }}
                        </p>
                        <template v-else>
                            <p class="text-sm text-gray-500">
                                {{ messages.total.toLocaleString() }}
                                {{
                                    messages.total === 1 ? 'thread' : 'threads'
                                }}
                                <span v-if="channel.message_count">
                                    ·
                                    {{ channel.message_count.toLocaleString() }}
                                    messages
                                </span>
                            </p>
                        </template>
                        <span
                            v-if="channel.member_count"
                            class="inline-flex items-center gap-1 text-sm text-gray-500"
                        >
                            <UserGroupIcon class="h-4 w-4" aria-hidden="true" />
                            {{ channel.member_count.toLocaleString() }} members
                        </span>
                    </div>
                </div>
                <button
                    class="inline-flex shrink-0 items-center gap-1.5 rounded-md bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50"
                    @click="toggleSort"
                >
                    <component
                        :is="
                            sortDirection === 'desc'
                                ? ArrowDownIcon
                                : ArrowUpIcon
                        "
                        class="h-4 w-4"
                        aria-hidden="true"
                    />
                    {{
                        sortDirection === 'desc'
                            ? 'Newest First'
                            : 'Oldest First'
                    }}
                </button>
            </div>

            <p v-if="channel.topic" class="mt-2 text-sm text-gray-600">
                {{ decodeEntities(channel.topic) }}
            </p>
            <p v-if="channel.purpose" class="mt-1 text-sm text-gray-500 italic">
                {{ decodeEntities(channel.purpose) }}
            </p>

            <!-- Filters -->
            <div class="mt-4 flex flex-wrap items-center gap-3">
                <div class="flex items-center gap-2">
                    <FunnelIcon
                        class="h-4 w-4 text-gray-400"
                        aria-hidden="true"
                    />
                    <select
                        v-model="dateMode"
                        aria-label="Date filter mode"
                        class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="onDateModeChange"
                    >
                        <option value="jump">Jump to date</option>
                        <option value="on">This day only</option>
                    </select>
                    <input
                        type="date"
                        aria-label="Date"
                        :value="dateValue"
                        :min="dateRange?.min?.split(' ')[0]"
                        :max="dateRange?.max?.split(' ')[0]"
                        class="rounded-md border-gray-300 py-1.5 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        @change="onDateChange"
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
                    <MapPinIcon class="h-4 w-4" aria-hidden="true" />
                    Pinned
                </button>

                <button
                    v-if="hasFilters"
                    class="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-sm text-gray-500 hover:text-gray-700"
                    @click="clearFilters"
                >
                    <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    Clear filters
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
                        : "This channel doesn't have any messages yet."
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
            <template #previous="{ loading, fetch, hasMore, manualMode }">
                <div v-if="hasMore" class="flex justify-center pb-2">
                    <button
                        v-if="manualMode"
                        class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:opacity-50"
                        :disabled="loading"
                        @click="fetch"
                    >
                        <Spinner v-if="loading" class="h-4 w-4" />
                        {{ newerLabel }}
                    </button>
                    <Spinner
                        v-else-if="loading"
                        class="h-5 w-5 text-gray-400"
                    />
                </div>
            </template>

            <template v-for="entry in timeline" :key="entry.key">
                <div
                    v-if="entry.type === 'divider'"
                    :id="`day-${entry.day}`"
                    class="sticky top-[4.25rem] z-10 flex scroll-mt-[4.5rem] justify-center py-1"
                >
                    <span
                        class="rounded-full border border-gray-200 bg-white px-3 py-1 text-xs font-semibold text-gray-600 shadow-sm"
                    >
                        {{ entry.label }}
                    </span>
                </div>
                <MessageCard
                    v-else
                    :id="`message-${entry.message.id}`"
                    :message="entry.message"
                    :show-date="false"
                    :channel-name="channel.name"
                    :flashed="flashedMessageId === entry.message.id"
                    @open-thread="openThread"
                />
            </template>

            <template #next="{ loading, fetch, hasMore, manualMode }">
                <div v-if="hasMore" class="flex justify-center pt-2">
                    <button
                        v-if="manualMode"
                        class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:opacity-50"
                        :disabled="loading"
                        @click="fetch"
                    >
                        <Spinner v-if="loading" class="h-4 w-4" />
                        {{ olderLabel }}
                    </button>
                    <Spinner
                        v-else-if="loading"
                        class="h-5 w-5 text-gray-400"
                    />
                </div>
            </template>
        </InfiniteScroll>

        <button
            v-if="hasJumpedAway"
            class="fixed right-6 bottom-6 z-30 inline-flex items-center gap-1.5 rounded-full bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-lg transition-colors hover:bg-gray-700"
            @click="backToTop"
        >
            <ArrowUpIcon class="h-4 w-4" aria-hidden="true" />
            {{ sortDirection === 'desc' ? 'Back to newest' : 'Back to oldest' }}
        </button>
    </div>

    <ThreadPanel
        :open="threadOpen"
        :message="threadMessage"
        :highlight-id="highlightId"
        @close="closeThread"
    />
</template>
