<script setup lang="ts">
import type { Message } from '@/types/app/Models/Message';
import AvatarStack from '@/components/AvatarStack.vue';
import ReactionPills from '@/components/ReactionPills.vue';
import { formatDateTime } from '@/utils/formatDate';
import {
    ChatBubbleLeftRightIcon,
    MapPinIcon,
    PaperClipIcon,
} from '@heroicons/vue/24/outline';
import { Link } from '@inertiajs/vue3';

defineProps<{
    message: Message;
    showChannel?: boolean;
}>();

const emit = defineEmits<{
    'open-thread': [message: Message];
}>();
</script>

<template>
    <div class="flex gap-3 rounded-lg border border-gray-100 bg-white p-4 shadow-sm transition-colors hover:border-gray-200">
        <div class="shrink-0">
            <img
                v-if="message.user.image_url"
                :src="message.user.image_url"
                :alt="message.user.name"
                class="h-9 w-9 rounded-md"
            />
            <div
                v-else
                class="flex h-9 w-9 items-center justify-center rounded-md bg-indigo-100 text-sm font-medium text-indigo-600"
            >
                {{ message.user.name.charAt(0).toUpperCase() }}
            </div>
        </div>

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                <Link
                    :href="`/users/${message.user.id}`"
                    class="text-sm font-semibold text-gray-900 hover:text-indigo-600 hover:underline"
                >
                    {{ message.user.name }}
                </Link>
                <span class="text-xs text-gray-500">{{ formatDateTime(message.slack_timestamp) }}</span>
                <span v-if="message.is_edited" class="text-xs text-gray-400">(edited)</span>
                <PaperClipIcon v-if="message.has_files" class="h-3.5 w-3.5 text-gray-400" title="Has attachments" />
                <MapPinIcon v-if="message.is_pinned" class="h-3.5 w-3.5 text-amber-500" title="Pinned" />
                <span
                    v-if="showChannel && message.channel"
                    class="inline-flex items-center rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                >
                    #{{ message.channel.name }}
                </span>
            </div>

            <div class="prose prose-sm mt-1 max-w-none break-words text-gray-700" v-html="message.content" />

            <button
                v-if="message.parent"
                class="mt-2 w-full cursor-pointer rounded border-l-2 border-indigo-300 bg-indigo-50 px-3 py-1.5 text-left transition-colors hover:bg-indigo-100"
                @click="emit('open-thread', message.parent!)"
            >
                <span class="text-xs font-medium text-indigo-600">
                    View thread — replying to {{ message.parent.user?.name ?? 'Unknown' }}
                </span>
            </button>

            <ReactionPills
                v-if="message.reactions?.length"
                :reactions="message.reactions"
                class="mt-2"
            />

            <div class="mt-2 flex items-center gap-3">
                <button
                    v-if="message.children_count && message.children_count > 0"
                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium text-indigo-600 transition-colors hover:bg-indigo-50 hover:text-indigo-800"
                    @click="emit('open-thread', message)"
                >
                    <ChatBubbleLeftRightIcon class="h-4 w-4" />
                    {{ message.children_count }} {{ message.children_count === 1 ? 'reply' : 'replies' }}
                </button>
                <AvatarStack
                    v-if="message.reply_users?.length"
                    :users="message.reply_users"
                />
            </div>
        </div>
    </div>
</template>
