<script setup lang="ts">
import {
    ChatBubbleLeftRightIcon,
    CheckIcon,
    LinkIcon,
    MapPinIcon,
    PaperClipIcon,
} from '@heroicons/vue/24/outline';
import { Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import AvatarStack from '@/components/AvatarStack.vue';
import ReactionPills from '@/components/ReactionPills.vue';
import Avatar from '@/components/ui/Avatar.vue';
import type { Message } from '@/types/app/Models/Message';
import { formatDateTime, formatTime } from '@/utils/formatDate';
import { highlightTerms } from '@/utils/slackContent';

const props = withDefaults(
    defineProps<{
        message: Message;
        showChannel?: boolean;
        showDate?: boolean;
        showThreadButton?: boolean;
        highlight?: string | null;
        channelName?: string | null;
        flashed?: boolean;
    }>(),
    {
        showChannel: false,
        showDate: true,
        showThreadButton: true,
        highlight: null,
        channelName: null,
        flashed: false,
    },
);

const emit = defineEmits<{
    'open-thread': [payload: { message: Message; highlightId: number | null }];
}>();

const copied = ref(false);

const renderedContent = computed(() =>
    props.highlight?.trim()
        ? highlightTerms(props.message.content_html, props.highlight)
        : props.message.content_html,
);

const hasTextContent = computed(() => props.message.content.trim().length > 0);

const contextChannelName = computed(
    () => props.message.channel?.name ?? props.channelName ?? null,
);

const messageDay = computed(() => props.message.slack_timestamp.split(' ')[0]);

const dayContextUrl = computed(() =>
    contextChannelName.value
        ? `/channels/${contextChannelName.value}?jump_date=${messageDay.value}`
        : null,
);

const timestampLabel = computed(() =>
    props.showDate
        ? formatDateTime(props.message.slack_timestamp)
        : formatTime(props.message.slack_timestamp),
);

function openThread(): void {
    emit('open-thread', { message: props.message, highlightId: null });
}

function openParentThread(): void {
    if (props.message.parent) {
        emit('open-thread', {
            message: props.message.parent,
            highlightId: props.message.id,
        });
    }
}

function copyLink(): void {
    const channelPath = contextChannelName.value
        ? `/channels/${contextChannelName.value}`
        : window.location.pathname;

    const url = props.message.parent_id
        ? `${channelPath}?thread=${props.message.parent_id}&msg=${props.message.id}`
        : `${channelPath}?jump_msg=${props.message.id}`;

    void navigator.clipboard.writeText(
        new URL(url, window.location.origin).toString(),
    );
    copied.value = true;
    setTimeout(() => (copied.value = false), 1500);
}

function handleContentClick(event: MouseEvent): void {
    const anchor = (event.target as HTMLElement).closest('a');

    if (anchor?.dataset.inertia !== undefined && anchor.getAttribute('href')) {
        event.preventDefault();
        router.visit(anchor.getAttribute('href')!);
    }
}
</script>

<template>
    <div
        class="group flex gap-3 rounded-lg border bg-white p-4 shadow-sm transition-colors"
        :class="
            flashed
                ? 'border-amber-300 bg-amber-50'
                : 'border-gray-100 hover:border-gray-200'
        "
    >
        <div class="shrink-0">
            <Link
                :href="`/users/${message.user.id}`"
                aria-hidden="true"
                tabindex="-1"
            >
                <Avatar
                    :name="message.user.name"
                    :image-url="message.user.image_url"
                />
            </Link>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <Link
                    :href="`/users/${message.user.id}`"
                    class="text-sm font-semibold text-gray-900 hover:text-indigo-600 hover:underline"
                >
                    {{ message.user.name }}
                </Link>
                <Link
                    v-if="dayContextUrl"
                    :href="dayContextUrl"
                    class="text-xs text-gray-500 hover:text-indigo-600 hover:underline"
                    :title="`View this day in #${contextChannelName}`"
                >
                    {{ timestampLabel }}
                </Link>
                <span v-else class="text-xs text-gray-500">{{
                    timestampLabel
                }}</span>
                <span v-if="message.is_edited" class="text-xs text-gray-400"
                    >(edited)</span
                >
                <PaperClipIcon
                    v-if="message.has_files && hasTextContent"
                    class="h-3.5 w-3.5 text-gray-400"
                    title="Has attachments"
                    aria-hidden="true"
                />
                <MapPinIcon
                    v-if="message.is_pinned"
                    class="h-3.5 w-3.5 text-amber-500"
                    title="Pinned"
                    aria-hidden="true"
                />
                <Link
                    v-if="showChannel && message.channel"
                    :href="`/channels/${message.channel.name}`"
                    class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700 hover:bg-indigo-100"
                >
                    #{{ message.channel.name }}
                </Link>

                <button
                    type="button"
                    class="ml-auto rounded-md p-1 text-gray-300 opacity-0 transition-opacity group-hover:opacity-100 hover:bg-gray-100 hover:text-gray-500 focus:opacity-100"
                    :class="{ 'opacity-100': copied }"
                    :aria-label="
                        copied ? 'Link copied' : 'Copy link to message'
                    "
                    :title="copied ? 'Copied!' : 'Copy link'"
                    @click="copyLink"
                >
                    <CheckIcon v-if="copied" class="h-4 w-4 text-green-600" />
                    <LinkIcon v-else class="h-4 w-4" />
                </button>
            </div>

            <div
                v-if="hasTextContent"
                class="msg-content mt-1 text-sm leading-relaxed break-words text-gray-700"
                @click="handleContentClick"
                v-html="renderedContent"
            />

            <div
                v-if="message.has_files && !hasTextContent"
                class="mt-2 inline-flex items-center gap-1.5 rounded-md border border-dashed border-gray-300 bg-gray-50 px-2.5 py-1.5 text-xs text-gray-500"
            >
                <PaperClipIcon class="h-3.5 w-3.5" aria-hidden="true" />
                File attachment — not included in this archive
            </div>

            <button
                v-if="message.parent"
                class="mt-2 w-full cursor-pointer rounded border-l-2 border-indigo-300 bg-indigo-50 px-3 py-1.5 text-left transition-colors hover:bg-indigo-100"
                @click="openParentThread"
            >
                <span class="text-xs font-medium text-indigo-600">
                    View thread — replying to
                    {{ message.parent.user?.name ?? 'Unknown' }}
                </span>
            </button>

            <ReactionPills
                v-if="message.reactions?.length"
                :reactions="message.reactions"
                class="mt-2"
            />

            <div
                v-if="showThreadButton"
                class="mt-2 flex items-center gap-3 empty:hidden"
            >
                <button
                    v-if="message.children_count && message.children_count > 0"
                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-800"
                    @click="openThread"
                >
                    <ChatBubbleLeftRightIcon
                        class="h-4 w-4"
                        aria-hidden="true"
                    />
                    {{ message.children_count }}
                    {{ message.children_count === 1 ? 'reply' : 'replies' }}
                </button>
                <AvatarStack
                    v-if="message.reply_users?.length"
                    :users="message.reply_users"
                />
            </div>
        </div>
    </div>
</template>
