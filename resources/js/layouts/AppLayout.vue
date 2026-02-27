<script setup lang="ts">
import type { Channel } from '@/types/app/Models/Channel';
import {
    Dialog,
    DialogPanel,
    TransitionChild,
    TransitionRoot,
} from '@headlessui/vue';
import {
    Bars3Icon,
    HashtagIcon,
    MagnifyingGlassIcon,
    UsersIcon,
    XMarkIcon,
} from '@heroicons/vue/24/outline';
import { Link, router, usePage } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';

const page = usePage();
const channels = computed(() => page.props.channels as Channel[]);
const appName = computed(() => page.props.name as string);

const sidebarOpen = ref(false);
const searchQuery = ref('');

const isMac = computed(() =>
    typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.userAgent),
);

const shortcutHint = computed(() => (isMac.value ? '\u2318K' : 'Ctrl+K'));

function handleSearch(): void {
    if (searchQuery.value.trim()) {
        router.get('/search', { q: searchQuery.value, sort_by: 'slack_timestamp', sort_direction: 'desc' });
        sidebarOpen.value = false;
    }
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

function isActiveChannel(channelName: string): boolean {
    return page.url.startsWith(`/channels/${channelName}`);
}

const isSearchActive = computed(() => page.url.startsWith('/search'));
const isUsersActive = computed(() => page.url.startsWith('/users'));
</script>

<template>
    <div class="min-h-screen bg-gray-50">
        <!-- Mobile sidebar -->
        <TransitionRoot as="template" :show="sidebarOpen">
            <Dialog class="relative z-50 lg:hidden" @close="sidebarOpen = false">
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
                        <DialogPanel class="relative mr-16 flex w-full max-w-xs flex-1">
                            <TransitionChild
                                as="template"
                                enter="ease-in-out duration-300"
                                enter-from="opacity-0"
                                enter-to="opacity-100"
                                leave="ease-in-out duration-300"
                                leave-from="opacity-100"
                                leave-to="opacity-0"
                            >
                                <div class="absolute top-0 left-full flex w-16 justify-center pt-5">
                                    <button class="-m-2.5 p-2.5" @click="sidebarOpen = false">
                                        <XMarkIcon class="h-6 w-6 text-white" />
                                    </button>
                                </div>
                            </TransitionChild>

                            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
                                <div class="flex h-16 shrink-0 items-center">
                                    <span class="text-lg font-bold text-white">{{ appName }}</span>
                                </div>
                                <nav class="flex flex-1 flex-col">
                                    <ul class="-mx-2 space-y-1">
                                        <li>
                                            <Link
                                                href="/search"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm font-medium"
                                                :class="
                                                    isSearchActive
                                                        ? 'bg-gray-800 text-white'
                                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                                                "
                                                @click="sidebarOpen = false"
                                            >
                                                <MagnifyingGlassIcon class="h-5 w-5 shrink-0" />
                                                Search
                                            </Link>
                                        </li>
                                        <li>
                                            <Link
                                                href="/users"
                                                class="group flex gap-x-3 rounded-md p-2 text-sm font-medium"
                                                :class="
                                                    isUsersActive
                                                        ? 'bg-gray-800 text-white'
                                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                                                "
                                                @click="sidebarOpen = false"
                                            >
                                                <UsersIcon class="h-5 w-5 shrink-0" />
                                                Users
                                            </Link>
                                        </li>
                                    </ul>

                                    <div class="mt-4 mb-2 px-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
                                        Channels
                                    </div>

                                    <ul class="-mx-2 space-y-1">
                                        <li v-for="channel in channels" :key="channel.id">
                                            <Link
                                                :href="`/channels/${channel.name}`"
                                                class="group flex items-center gap-x-3 rounded-md p-2 text-sm font-medium"
                                                :class="
                                                    isActiveChannel(channel.name)
                                                        ? 'bg-gray-800 text-white'
                                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                                                "
                                                @click="sidebarOpen = false"
                                            >
                                                <HashtagIcon class="h-5 w-5 shrink-0" />
                                                {{ channel.name }}
                                                <span v-if="channel.message_count" class="ml-auto text-xs text-gray-500">
                                                    {{ channel.message_count.toLocaleString() }}
                                                </span>
                                            </Link>
                                        </li>
                                    </ul>
                                </nav>
                            </div>
                        </DialogPanel>
                    </TransitionChild>
                </div>
            </Dialog>
        </TransitionRoot>

        <!-- Desktop sidebar -->
        <div class="hidden lg:fixed lg:inset-y-0 lg:z-50 lg:flex lg:w-60 lg:flex-col">
            <div class="flex grow flex-col gap-y-5 overflow-y-auto bg-gray-900 px-6 pb-4">
                <div class="flex h-16 shrink-0 items-center">
                    <span class="text-lg font-bold text-white">{{ appName }}</span>
                </div>
                <nav class="flex flex-1 flex-col">
                    <ul class="-mx-2 space-y-1">
                        <li>
                            <Link
                                href="/search"
                                class="group flex gap-x-3 rounded-md p-2 text-sm font-medium"
                                :class="
                                    isSearchActive
                                        ? 'bg-gray-800 text-white'
                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                                "
                            >
                                <MagnifyingGlassIcon class="h-5 w-5 shrink-0" />
                                Search
                            </Link>
                        </li>
                        <li>
                            <Link
                                href="/users"
                                class="group flex gap-x-3 rounded-md p-2 text-sm font-medium"
                                :class="
                                    isUsersActive
                                        ? 'bg-gray-800 text-white'
                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                                "
                            >
                                <UsersIcon class="h-5 w-5 shrink-0" />
                                Users
                            </Link>
                        </li>
                    </ul>

                    <div class="mt-4 mb-2 px-2 text-xs font-semibold tracking-wider text-gray-500 uppercase">
                        Channels
                    </div>

                    <ul class="-mx-2 flex-1 space-y-1 overflow-y-auto">
                        <li v-for="channel in channels" :key="channel.id">
                            <Link
                                :href="`/channels/${channel.name}`"
                                class="group flex items-center gap-x-3 rounded-md p-2 text-sm font-medium"
                                :class="
                                    isActiveChannel(channel.name)
                                        ? 'bg-gray-800 text-white'
                                        : 'text-gray-400 hover:bg-gray-800 hover:text-white'
                                "
                            >
                                <HashtagIcon class="h-5 w-5 shrink-0" />
                                {{ channel.name }}
                                <span v-if="channel.message_count" class="ml-auto text-xs text-gray-500">
                                    {{ channel.message_count.toLocaleString() }}
                                </span>
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>

        <!-- Main content -->
        <div class="lg:pl-60">
            <!-- Top bar -->
            <div class="sticky top-0 z-40 flex h-16 shrink-0 items-center gap-x-4 border-b border-gray-200 bg-white px-4 shadow-sm sm:gap-x-6 sm:px-6 lg:px-8">
                <button class="-m-2.5 p-2.5 text-gray-700 lg:hidden" @click="sidebarOpen = true">
                    <Bars3Icon class="h-6 w-6" />
                </button>

                <div class="h-6 w-px bg-gray-200 lg:hidden" />

                <div class="flex flex-1 gap-x-4 self-stretch lg:gap-x-6">
                    <form class="relative flex flex-1" @submit.prevent="handleSearch">
                        <MagnifyingGlassIcon
                            class="pointer-events-none absolute inset-y-0 left-0 h-full w-5 text-gray-400"
                        />
                        <input
                            id="desktop-search"
                            v-model="searchQuery"
                            type="search"
                            :placeholder="`Search messages... (${shortcutHint})`"
                            class="block h-full w-full border-0 py-0 pr-0 pl-8 text-gray-900 placeholder:text-gray-400 focus:ring-0 sm:text-sm"
                        />
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
