<script setup lang="ts">
import { computed } from 'vue';
import Avatar from '@/components/ui/Avatar.vue';
import type { ReplyUser } from '@/types/app/Models/Message';

const props = withDefaults(
    defineProps<{
        users: ReplyUser[];
        max?: number;
    }>(),
    { max: 3 },
);

const visibleUsers = computed(() => props.users.slice(0, props.max));
const overflowCount = computed(() =>
    Math.max(0, props.users.length - props.max),
);
</script>

<template>
    <div class="flex items-center">
        <div class="flex -space-x-2">
            <Avatar
                v-for="(user, index) in visibleUsers"
                :key="index"
                :name="user.name"
                :image-url="user.image_url"
                size-class="h-6 w-6"
                rounded-class="rounded-full"
                text-class="text-[10px]"
                class="ring-2 ring-white"
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
