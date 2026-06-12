<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    DialogTitle,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import { XMarkIcon } from '@heroicons/vue/24/outline';
import { useIntersectionObserver } from '@vueuse/core';
import { nextTick, ref, watch } from 'vue';
import MessageCard from '@/components/MessageCard.vue';
import Spinner from '@/components/ui/Spinner.vue';
import type { Message } from '@/types/app/Models/Message';
import type { PaginatedResponse } from '@/types/pagination';

const props = defineProps<{
    open: boolean;
    message: Message | null;
    highlightId?: number | null;
}>();

const emit = defineEmits<{
    close: [];
}>();

const children = ref<Message[]>([]);
const totalReplies = ref<number | null>(null);
const loading = ref(false);
const nextPageUrl = ref<string | null>(null);
const loadingMore = ref(false);
const flashedId = ref<number | null>(null);
const loadMoreTrigger = ref<HTMLElement | null>(null);

watch(
    () => [props.open, props.message?.id],
    async ([open]) => {
        if (open && props.message) {
            children.value = [];
            nextPageUrl.value = null;
            totalReplies.value = null;
            await fetchChildren(props.message.id);
            await locateHighlight();
        }
    },
    { immediate: true },
);

useIntersectionObserver(loadMoreTrigger, ([entry]) => {
    if (entry?.isIntersecting) {
        loadMore();
    }
});

async function fetchChildren(messageId: number, url?: string): Promise<void> {
    if (url) {
        loadingMore.value = true;
    } else {
        loading.value = true;
    }

    try {
        const fetchUrl = url ?? `/api/messages/${messageId}/children`;
        const response = await fetch(fetchUrl);
        const data: PaginatedResponse<Message> = await response.json();

        if (url) {
            children.value.push(...data.data);
        } else {
            children.value = data.data;
        }
        totalReplies.value = data.total;
        nextPageUrl.value = data.next_page_url;
    } finally {
        loading.value = false;
        loadingMore.value = false;
    }
}

async function locateHighlight(): Promise<void> {
    const targetId = props.highlightId;

    if (!targetId || !props.message) {
        return;
    }

    let pageGuard = 0;

    while (
        !children.value.some((child) => child.id === targetId) &&
        nextPageUrl.value &&
        pageGuard < 10
    ) {
        pageGuard++;
        await fetchChildren(props.message.id, nextPageUrl.value);
    }

    await nextTick();
    document
        .getElementById(`thread-message-${targetId}`)
        ?.scrollIntoView({ block: 'center' });
    flashedId.value = targetId;
    setTimeout(() => (flashedId.value = null), 2500);
}

function loadMore(): void {
    if (
        nextPageUrl.value &&
        props.message &&
        !loadingMore.value &&
        !loading.value
    ) {
        void fetchChildren(props.message.id, nextPageUrl.value);
    }
}
</script>

<template>
    <TransitionRoot as="template" :show="open">
        <Dialog class="relative z-50" @close="emit('close')">
            <TransitionChild
                as="template"
                enter="ease-in-out duration-300"
                enter-from="opacity-0"
                enter-to="opacity-100"
                leave="ease-in-out duration-300"
                leave-from="opacity-100"
                leave-to="opacity-0"
            >
                <div class="fixed inset-0 bg-gray-500/75 transition-opacity" />
            </TransitionChild>

            <div class="fixed inset-0 overflow-hidden">
                <div class="absolute inset-0 overflow-hidden">
                    <div
                        class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10"
                    >
                        <TransitionChild
                            as="template"
                            enter="transform transition ease-in-out duration-300"
                            enter-from="translate-x-full"
                            enter-to="translate-x-0"
                            leave="transform transition ease-in-out duration-300"
                            leave-from="translate-x-0"
                            leave-to="translate-x-full"
                        >
                            <DialogPanel
                                class="pointer-events-auto w-screen max-w-lg"
                            >
                                <div
                                    class="flex h-full flex-col overflow-y-auto bg-gray-50 shadow-xl"
                                >
                                    <div
                                        class="sticky top-0 z-10 border-b border-gray-200 bg-white px-4 py-4 sm:px-6"
                                    >
                                        <div
                                            class="flex items-start justify-between"
                                        >
                                            <DialogTitle
                                                class="text-base font-semibold text-gray-900"
                                            >
                                                Thread
                                                <span
                                                    v-if="message?.channel"
                                                    class="ml-1 text-sm font-normal text-gray-500"
                                                >
                                                    in #{{
                                                        message.channel.name
                                                    }}
                                                </span>
                                            </DialogTitle>
                                            <button
                                                class="rounded-md text-gray-400 hover:text-gray-500 focus:ring-2 focus:ring-indigo-500 focus:outline-none"
                                                aria-label="Close thread"
                                                @click="emit('close')"
                                            >
                                                <XMarkIcon
                                                    class="h-6 w-6"
                                                    aria-hidden="true"
                                                />
                                            </button>
                                        </div>
                                    </div>

                                    <div class="flex-1 px-4 py-4 sm:px-6">
                                        <div v-if="message" class="space-y-4">
                                            <MessageCard
                                                :message="message"
                                                :show-thread-button="false"
                                                :channel-name="
                                                    message.channel?.name
                                                "
                                                class="border-indigo-200"
                                            />

                                            <div
                                                v-if="loading"
                                                class="flex justify-center py-8"
                                            >
                                                <Spinner
                                                    class="h-6 w-6 text-indigo-600"
                                                />
                                            </div>

                                            <template v-else>
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <div
                                                        class="h-px flex-1 bg-gray-200"
                                                    />
                                                    <span
                                                        class="text-xs font-medium text-gray-500"
                                                    >
                                                        {{
                                                            totalReplies ??
                                                            children.length
                                                        }}
                                                        {{
                                                            (totalReplies ??
                                                                children.length) ===
                                                            1
                                                                ? 'reply'
                                                                : 'replies'
                                                        }}
                                                    </span>
                                                    <div
                                                        class="h-px flex-1 bg-gray-200"
                                                    />
                                                </div>

                                                <div class="space-y-3">
                                                    <div
                                                        v-for="child in children"
                                                        :id="`thread-message-${child.id}`"
                                                        :key="child.id"
                                                    >
                                                        <MessageCard
                                                            :message="child"
                                                            :show-thread-button="
                                                                false
                                                            "
                                                            :channel-name="
                                                                message.channel
                                                                    ?.name
                                                            "
                                                            :flashed="
                                                                flashedId ===
                                                                child.id
                                                            "
                                                        />
                                                    </div>

                                                    <div
                                                        v-if="nextPageUrl"
                                                        ref="loadMoreTrigger"
                                                        class="flex justify-center pt-2"
                                                    >
                                                        <button
                                                            class="inline-flex items-center gap-2 rounded-md bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm ring-1 ring-gray-300 ring-inset hover:bg-gray-50 disabled:opacity-50"
                                                            :disabled="
                                                                loadingMore
                                                            "
                                                            @click="loadMore"
                                                        >
                                                            <Spinner
                                                                v-if="
                                                                    loadingMore
                                                                "
                                                                class="h-4 w-4"
                                                            />
                                                            Load More Replies
                                                        </button>
                                                    </div>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </DialogPanel>
                        </TransitionChild>
                    </div>
                </div>
            </div>
        </Dialog>
    </TransitionRoot>
</template>
