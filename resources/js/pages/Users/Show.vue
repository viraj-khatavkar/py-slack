<script setup lang="ts">
import type { User } from '@/types/app/Models/User';
import type { Message } from '@/types/app/Models/Message';
import type { PaginatedResponse } from '@/types/pagination';
import MessageCard from '@/components/MessageCard.vue';
import ThreadPanel from '@/components/ThreadPanel.vue';
import Spinner from '@/components/ui/Spinner.vue';
import {
    ChatBubbleLeftRightIcon,
    ClockIcon,
    ShieldCheckIcon,
} from '@heroicons/vue/24/outline';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    user: User;
    messages: PaginatedResponse<Message>;
}>();

const allMessages = ref<Message[]>([...props.messages.data]);
const nextPageUrl = ref<string | null>(props.messages.next_page_url);
const loadingMore = ref(false);

const threadOpen = ref(false);
const threadMessage = ref<Message | null>(null);

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
    <Head :title="user.name" />

    <div>
        <!-- Profile header -->
        <div class="mb-6 rounded-lg border border-gray-100 bg-white p-6 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="shrink-0">
                    <img
                        v-if="user.image_url"
                        :src="user.image_url"
                        :alt="user.name"
                        class="h-16 w-16 rounded-lg"
                    />
                    <div
                        v-else
                        class="flex h-16 w-16 items-center justify-center rounded-lg bg-indigo-100 text-xl font-medium text-indigo-600"
                    >
                        {{ user.name.charAt(0).toUpperCase() }}
                    </div>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-gray-900">{{ user.name }}</h1>
                        <span
                            v-if="user.is_admin"
                            class="inline-flex items-center gap-1 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-medium text-indigo-700"
                        >
                            <ShieldCheckIcon class="h-3 w-3" />
                            Admin
                        </span>
                    </div>
                    <p v-if="user.title" class="mt-0.5 text-sm text-gray-600">{{ user.title }}</p>
                    <div class="mt-2 flex flex-wrap items-center gap-4 text-sm text-gray-500">
                        <span v-if="user.timezone_label" class="inline-flex items-center gap-1">
                            <ClockIcon class="h-4 w-4" />
                            {{ user.timezone_label }}
                        </span>
                        <span class="inline-flex items-center gap-1">
                            <ChatBubbleLeftRightIcon class="h-4 w-4" />
                            {{ (user.messages_count ?? 0).toLocaleString() }} messages
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Messages -->
        <h2 class="mb-4 text-lg font-semibold text-gray-900">Messages</h2>

        <div v-if="allMessages.length === 0" class="rounded-lg border border-gray-200 bg-white py-16 text-center">
            <ChatBubbleLeftRightIcon class="mx-auto h-12 w-12 text-gray-300" />
            <h3 class="mt-3 text-sm font-semibold text-gray-900">No messages</h3>
            <p class="mt-1 text-sm text-gray-500">This user hasn't posted any messages.</p>
        </div>

        <div v-else class="space-y-3">
            <MessageCard
                v-for="message in allMessages"
                :key="message.id"
                :message="message"
                :show-channel="true"
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
