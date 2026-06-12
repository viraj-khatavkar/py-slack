<script setup lang="ts">
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import {
    Bars3Icon,
    MagnifyingGlassIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import SidebarNav from '@/components/SidebarNav.vue';
import type { Channel } from '@/types/app/Models/Channel';

const page = usePage();
const appName = computed(() => page.props.name as string);

const sidebarOpen = ref(false);
const searchQuery = ref('');
const searchScoped = ref(true);

const isMac = computed(
    () =>
        typeof navigator !== 'undefined' &&
        /Mac|iPhone|iPad/.test(navigator.userAgent),
);

const shortcutHint = computed(() => (isMac.value ? '⌘K' : 'Ctrl+K'));

const currentChannel = computed(() => {
    if (!page.url.startsWith('/channels/')) {
        return null;
    }

    return (page.props.channel as Channel | undefined) ?? null;
});

watch(
    () => page.url,
    (url) => {
        searchScoped.value = true;

        if (url.startsWith('/search')) {
            const queryString = url.split('?')[1] ?? '';
            searchQuery.value = new URLSearchParams(queryString).get('q') ?? '';
        }
    },
    { immediate: true },
);

function handleSearch(): void {
    if (!searchQuery.value.trim()) {
        return;
    }

    const params: Record<string, string> = {
        q: searchQuery.value,
        sort_by: 'slack_timestamp',
        sort_direction: 'desc',
    };

    if (searchScoped.value && currentChannel.value) {
        params.channel_id = String(currentChannel.value.id);
    }

    router.get('/search', params);
    sidebarOpen.value = false;
}

function handleKeydown(e: KeyboardEvent): void {
    if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
        e.preventDefault();
        const el = document.getElementById('desktop-search');
        if (el) {
            el.focus();
        }
    }
}

onMounted(() => {
    document.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    document.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile sidebar -->
        <TransitionRoot as="template" :show="sidebarOpen">
            <Dialog
                class="relative z-50 lg:hidden"
                @close="sidebarOpen = false"
            >
                <TransitionChild
                    as="template"
                    enter="transition-opacity ease-linear duration-300"
                    enter-from="opacity-0"
                    enter-to="opacity-100"
                    leave="transition-opacity ease-linear duration-300"
                    leave-from="opacity-100"
                    leave-to="opacity-0"
                >
                    <div class="fixed inset-0 bg-gray-900/80" />
                </TransitionChild>

                <div class="fixed inset-0 flex">
                    <TransitionChild
                        as="template"
                        enter="transition ease-in-out duration-300 transform"
                        enter-from="-translate-x-full"
                        enter-to="translate-x-0"
                        leave="transition ease-in-out duration-300 transform"
                        leave-from="translate-x-0"
                        leave-to="-translate-x-full"
                    >
                        <DialogPanel
                            class="relative mr-16 flex w-full max-w-xs flex-1"
                        >
                            <TransitionChild
                                as="template"
                                enter="ease-in-out duration-300"
                                enter-from="opacity-0"
                                enter-to="opacity-100"
                                leave="ease-in-out duration-300"
                                leave-from="opacity-100"
                                leave-to="opacity-0"
                            >
                                <div
                                    class="absolute top-0 left-full flex w-16 justify-center pt-5"
                                >
                                    <button
                                        class="-m-2.5 p-2.5"
                                        aria-label="Close navigation"
                                        @click="sidebarOpen = false"
                                    >
                                        <XMarkIcon
                                            class="h-6 w-6 text-white"
                                            aria-hidden="true"
                                        />
                                    </button>
                                </div>
                            </TransitionChild>

                            <div
                                class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4"
                            >
                                <div class="flex h-16 shrink-0 items-center">
                                    <Link
                                        href="/"
                                        class="text-lg font-bold text-white"
                                        @click="sidebarOpen = false"
                                    >
                                        {{ appName }}
                                    </Link>
                                </div>
                                <SidebarNav @navigate="sidebarOpen = false" />
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Desktop sidebar -->
        <div
            class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-60 lg:flex-col"
        >
            <div
                class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4"
            >
                <div class="flex h-16 shrink-0 items-center">
                    <Link href="/" class="text-lg font-bold text-white">{{
                        appName
                    }}</Link>
                </div>
                <SidebarNav />
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-60">
            <!-- Top bar -->
            <div
                class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8"
            >
                <button
                    class="-m-2.5 p-2.5 text-gray-700 lg:hidden"
                    aria-label="Open navigation"
                    @click="sidebarOpen = true"
                >
                    <Bars3Icon class="h-6 w-6" aria-hidden="true" />
                </button>

                <div class="h-6 w-px bg-gray-200 lg:hidden" />

                <div
                    class="flex flex-1 items-center gap-x-4 self-stretch lg:gap-x-6"
                >
                    <form
                        class="relative flex flex-1 items-center"
                        @submit.prevent="handleSearch"
                    >
                        <MagnifyingGlassIcon
                            class="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-gray-400"
                            aria-hidden="true"
                        />
                        <input
                            id="desktop-search"
                            v-model="searchQuery"
                            type="search"
                            aria-label="Search messages"
                            :placeholder="`Search messages... (${shortcutHint})`"
                            class="block h-full w-full border-0 py-0 pr-0 pl-8 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm"
                        />
                        <button
                            v-if="currentChannel"
                            type="button"
                            class="inline-flex shrink-0 items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium transition-colors"
                            :class="
                                searchScoped
                                    ? 'bg-indigo-50 text-indigo-700 hover:bg-indigo-100'
                                    : 'bg-gray-100 text-gray-400 line-through hover:bg-gray-200'
                            "
                            :title="
                                searchScoped
                                    ? `Searching only #${currentChannel.name} — click to search everywhere`
                                    : `Click to search only #${currentChannel.name}`
                            "
                            @click="searchScoped = !searchScoped"
                        >
                            in #{{ currentChannel.name }}
                            <XMarkIcon
                                v-if="searchScoped"
                                class="h-3 w-3"
                                aria-hidden="true"
                            />
                        </button>
                    </form>
                </div>
            </div>

            <!-- Page content -->
            <main class="py-6">
                <div class="px-4 sm:px-6 lg:px-8">
                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>
