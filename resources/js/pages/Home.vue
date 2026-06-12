<script setup lang="ts">
import {
    CalendarDaysIcon,
    ChatBubbleLeftRightIcon,
    HashtagIcon,
    UserGroupIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Channel } from '@/types/app/Models/Channel';
import { formatDate } from '@/utils/formatDate';
import { decodeEntities } from '@/utils/slackContent';

interface ChannelActivity {
    channel_id: number;
    first_message_at: string | null;
    last_message_at: string | null;
}

const props = defineProps<{
    stats: {
        messages: number;
        threads: number;
        users: number;
        channels: number;
        oldest: string | null;
        newest: string | null;
    };
    channelActivity: Record<string, ChannelActivity>;
}>();

const page = usePage();
const appName = computed(() => page.props.name as string);

const channels = computed(() =>
    [...(page.props.channels as Channel[])].sort(
        (a, b) => (b.message_count ?? 0) - (a.message_count ?? 0),
    ),
);

const archiveSpan = computed(() => {
    if (!props.stats.oldest || !props.stats.newest) {
        return null;
    }

    return `${formatDate(props.stats.oldest, 'MMM YYYY')} – ${formatDate(props.stats.newest, 'MMM YYYY')}`;
});

function activityFor(channel: Channel): ChannelActivity | undefined {
    return props.channelActivity[String(channel.id)];
}

const statCards = computed(() => [
    {
        label: 'Messages',
        value: props.stats.messages.toLocaleString(),
        icon: ChatBubbleLeftRightIcon,
    },
    {
        label: 'Threads',
        value: props.stats.threads.toLocaleString(),
        icon: ChatBubbleLeftRightIcon,
    },
    {
        label: 'Members',
        value: props.stats.users.toLocaleString(),
        icon: UsersIcon,
    },
    {
        label: 'Channels',
        value: props.stats.channels.toLocaleString(),
        icon: HashtagIcon,
    },
]);
</script>

<template>
    <Head title="Home" />

    <div>
        <div class="mb-8">
            <h1 class="text-2xl font-bold text-gray-900">{{ appName }}</h1>
            <p class="mt-1 flex items-center gap-1.5 text-sm text-gray-500">
                <CalendarDaysIcon class="h-4 w-4" aria-hidden="true" />
                <template v-if="archiveSpan"
                    >Read-only archive · {{ archiveSpan }}</template
                >
                <template v-else>Read-only archive</template>
            </p>
        </div>

        <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="stat in statCards"
                :key="stat.label"
                class="rounded-lg border border-gray-100 bg-white p-4 shadow-sm"
            >
                <div class="flex items-center gap-2 text-sm text-gray-500">
                    <component
                        :is="stat.icon"
                        class="h-4 w-4"
                        aria-hidden="true"
                    />
                    {{ stat.label }}
                </div>
                <div class="mt-1 text-2xl font-bold text-gray-900">
                    {{ stat.value }}
                </div>
            </div>
        </div>

        <h2 class="mb-4 text-lg font-semibold text-gray-900">Channels</h2>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
            <Link
                v-for="channel in channels"
                :key="channel.id"
                :href="`/channels/${channel.name}`"
                class="flex flex-col gap-2 rounded-lg border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-indigo-200 hover:bg-indigo-50/30"
            >
                <div class="flex items-center justify-between gap-2">
                    <span
                        class="flex min-w-0 items-center gap-1 text-sm font-semibold text-gray-900"
                    >
                        <HashtagIcon
                            class="h-4 w-4 shrink-0 text-gray-400"
                            aria-hidden="true"
                        />
                        <span class="truncate">{{ channel.name }}</span>
                    </span>
                    <span
                        v-if="channel.member_count"
                        class="inline-flex shrink-0 items-center gap-1 text-xs text-gray-400"
                    >
                        <UserGroupIcon class="h-3.5 w-3.5" aria-hidden="true" />
                        {{ channel.member_count.toLocaleString() }}
                    </span>
                </div>

                <p
                    v-if="channel.topic"
                    class="line-clamp-2 text-xs text-gray-500"
                >
                    {{ decodeEntities(channel.topic) }}
                </p>

                <div
                    class="mt-auto flex items-center justify-between gap-2 text-xs text-gray-400"
                >
                    <span
                        >{{
                            (channel.message_count ?? 0).toLocaleString()
                        }}
                        messages</span
                    >
                    <span v-if="activityFor(channel)?.first_message_at">
                        {{
                            formatDate(
                                activityFor(channel)!.first_message_at!,
                                'MMM YYYY',
                            )
                        }}
                        –
                        {{
                            formatDate(
                                activityFor(channel)!.last_message_at!,
                                'MMM YYYY',
                            )
                        }}
                    </span>
                </div>
            </Link>
        </div>
    </div>
</template>
