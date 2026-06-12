<script setup lang="ts">
import { onClickOutside } from '@vueuse/core';
import { ref } from 'vue';
import type { Reaction } from '@/types/app/Models/Message';
import { slackEmojiToUnicode } from '@/utils/emojiMap';

defineProps<{
    reactions: Reaction[];
}>();

const root = ref<HTMLElement | null>(null);
const openIndex = ref<number | null>(null);

onClickOutside(root, () => {
    openIndex.value = null;
});

function toggle(index: number): void {
    openIndex.value = openIndex.value === index ? null : index;
}
</script>

<template>
    <div ref="root" class="flex flex-wrap gap-1.5">
        <div
            v-for="(reaction, index) in reactions"
            :key="index"
            class="relative"
        >
            <button
                type="button"
                class="inline-flex items-center gap-1 rounded-full border px-2 py-0.5 text-xs transition-colors"
                :class="
                    openIndex === index
                        ? 'border-indigo-300 bg-indigo-50'
                        : 'border-gray-200 bg-gray-50 hover:border-gray-300 hover:bg-gray-100'
                "
                :aria-expanded="openIndex === index"
                :aria-label="`${reaction.name} reaction from ${reaction.users.join(', ')}`"
                @click="toggle(index)"
            >
                <span>{{ slackEmojiToUnicode(reaction.name) }}</span>
                <span class="font-medium text-gray-600">{{
                    reaction.count
                }}</span>
            </button>

            <div
                v-if="openIndex === index"
                class="absolute bottom-full left-0 z-20 mb-1.5 w-max max-w-60 rounded-md bg-gray-900 px-3 py-2 text-xs text-gray-100 shadow-lg"
            >
                <span class="mr-1">{{
                    slackEmojiToUnicode(reaction.name)
                }}</span>
                <span class="max-h-32 overflow-y-auto">{{
                    reaction.users.join(', ')
                }}</span>
            </div>
        </div>
    </div>
</template>
