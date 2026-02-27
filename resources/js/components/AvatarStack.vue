<script setup lang="ts">
import type { ReplyUser } from '@/types/app/Models/Message';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        users: ReplyUser[];
        max?: number;
    }>(),
    { max: 3 },
);

const visibleUsers = computed(() => props.users.slice(0, props.max));
const overflowCount = computed(() => Math.max(0, props.users.length - props.max));
</script>

<template>
    <div class="flex items-center">
        <div class="flex -space-x-2">
            <img
                v-for="(user, index) in visibleUsers"
                :key="index"
                :src="user.image_url ?? undefined"
                :alt="user.name"
                :title="user.name"
                class="inline-block h-6 w-6 rounded-full ring-2 ring-white"
            />
            <span
                v-if="overflowCount > 0"
                class="inline-flex h-6 w-6 items-center justify-center rounded-full bg-gray-200 text-[10px] font-medium text-gray-600 ring-2 ring-white"
            >
                +{{ overflowCount }}
            </span>
        </div>
    </div>
</template>
