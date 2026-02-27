<script setup lang="ts">
import type { Channel } from '@/types/app/Models/Channel';
import PrimaryButton from '@/components/ui/PrimaryButton.vue';
import LightButton from '@/components/ui/LightButton.vue';
import { usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    filters: {
        q: string | null;
        from_date: string | null;
        to_date: string | null;
        channel_id: string | null;
        sort_by: string;
        sort_direction: string;
    };
}>();

const emit = defineEmits<{
    search: [params: Record<string, string>];
}>();

const channels = computed(() => usePage().props.channels as Channel[]);

const q = ref(props.filters.q ?? '');
const fromDate = ref(props.filters.from_date ?? '');
const toDate = ref(props.filters.to_date ?? '');
const channelId = ref(props.filters.channel_id ?? '');
const sortBy = ref(props.filters.sort_by ?? 'slack_timestamp');
const sortDirection = ref(props.filters.sort_direction ?? 'desc');

const hasFilters = computed(() =>
    Boolean(fromDate.value || toDate.value || channelId.value),
);

function submit(): void {
    const params: Record<string, string> = {};

    if (q.value) {
        params.q = q.value;
    }
    if (fromDate.value) {
        params.from_date = fromDate.value;
    }
    if (toDate.value) {
        params.to_date = toDate.value;
    }
    if (channelId.value) {
        params.channel_id = channelId.value;
    }
    params.sort_by = sortBy.value;
    params.sort_direction = sortDirection.value;

    emit('search', params);
}

function reset(): void {
    q.value = '';
    fromDate.value = '';
    toDate.value = '';
    channelId.value = '';
    sortBy.value = 'slack_timestamp';
    sortDirection.value = 'desc';
}
</script>

<template>
    <form class="space-y-4 rounded-lg border border-gray-200 bg-white p-5 shadow-sm" @submit.prevent="submit">
        <div>
            <label for="search-q" class="block text-sm font-medium text-gray-700">Search</label>
            <input
                id="search-q"
                v-model="q"
                type="text"
                placeholder="Search messages..."
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
            />
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div>
                <label for="from-date" class="block text-sm font-medium text-gray-700">From Date</label>
                <input
                    id="from-date"
                    v-model="fromDate"
                    type="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
            </div>
            <div>
                <label for="to-date" class="block text-sm font-medium text-gray-700">To Date</label>
                <input
                    id="to-date"
                    v-model="toDate"
                    type="date"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                />
            </div>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label for="channel" class="block text-sm font-medium text-gray-700">Channel</label>
                <select
                    id="channel"
                    v-model="channelId"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="">All Channels</option>
                    <option v-for="channel in channels" :key="channel.id" :value="channel.id">
                        #{{ channel.name }}
                    </option>
                </select>
            </div>
            <div>
                <label for="sort-by" class="block text-sm font-medium text-gray-700">Sort By</label>
                <select
                    id="sort-by"
                    v-model="sortBy"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="slack_timestamp">Date</option>
                    <option value="children_count">Reply Count</option>
                </select>
            </div>
            <div>
                <label for="sort-dir" class="block text-sm font-medium text-gray-700">Direction</label>
                <select
                    id="sort-dir"
                    v-model="sortDirection"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                >
                    <option value="desc">Newest First</option>
                    <option value="asc">Oldest First</option>
                </select>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <PrimaryButton type="submit">Search</PrimaryButton>
            <LightButton v-if="hasFilters" type="button" @click="reset">Reset Filters</LightButton>
        </div>
    </form>
</template>
