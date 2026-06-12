<script setup lang="ts">
import {
    HashtagIcon,
    HomeIcon,
    MagnifyingGlassIcon,
    UsersIcon,
} from '@heroicons/vue/24/outline';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Channel } from '@/types/app/Models/Channel';

const emit = defineEmits<{
    navigate: [];
}>();

const page = usePage();
const channels = computed(() => page.props.channels as Channel[]);

const isHomeActive = computed(
    () => page.url === '/' || page.url.startsWith('/?'),
);
const isSearchActive = computed(() => page.url.startsWith('/search'));
const isUsersActive = computed(() => page.url.startsWith('/users'));

function isActiveChannel(channelName: string): boolean {
    return page.url.startsWith(`/channels/${channelName}`);
}

const linkClasses = 'group flex gap-x-3 rounded-md p-2 text-sm font-medium';
const activeClasses = 'bg-gray-800 text-white';
const inactiveClasses = 'text-gray-400 hover:bg-gray-800 hover:text-white';
</script>

<template>
    <nav class="flex flex-1 flex-col">
        <ul class="-mx-2 space-y-1">
            <li>
                <Link
                    href="/"
                    :class="[
                        linkClasses,
                        isHomeActive ? activeClasses : inactiveClasses,
                    ]"
                    @click="emit('navigate')"
                >
                    <HomeIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    Home
                </Link>
            </li>
            <li>
                <Link
                    href="/search"
                    :class="[
                        linkClasses,
                        isSearchActive ? activeClasses : inactiveClasses,
                    ]"
                    @click="emit('navigate')"
                >
                    <MagnifyingGlassIcon
                        class="h-5 w-5 shrink-0"
                        aria-hidden="true"
                    />
                    Search
                </Link>
            </li>
            <li>
                <Link
                    href="/users"
                    :class="[
                        linkClasses,
                        isUsersActive ? activeClasses : inactiveClasses,
                    ]"
                    @click="emit('navigate')"
                >
                    <UsersIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    Users
                </Link>
            </li>
        </ul>

        <div
            class="mt-4 mb-2 px-2 text-xs font-semibold tracking-wider text-gray-500 uppercase"
        >
            Channels
        </div>

        <ul class="-mx-2 flex-1 space-y-1 overflow-y-auto">
            <li v-for="channel in channels" :key="channel.id">
                <Link
                    :href="`/channels/${channel.name}`"
                    class="items-center"
                    :class="[
                        linkClasses,
                        isActiveChannel(channel.name)
                            ? activeClasses
                            : inactiveClasses,
                    ]"
                    @click="emit('navigate')"
                >
                    <HashtagIcon class="h-5 w-5 shrink-0" aria-hidden="true" />
                    {{ channel.name }}
                    <span
                        v-if="channel.message_count"
                        class="ml-auto text-xs text-gray-500"
                    >
                        {{ channel.message_count.toLocaleString() }}
                    </span>
                </Link>
            </li>
        </ul>
    </nav>
</template>
