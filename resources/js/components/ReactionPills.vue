<script setup lang="ts">
import type { Reaction } from '@/types/app/Models/Message';
import { slackEmojiToUnicode } from '@/utils/emojiMap';

defineProps<{
    reactions: Reaction[];
}>();
</script>

<template>
    <div class="flex flex-wrap gap-1.5">
        <span
            v-for="(reaction, index) in reactions"
            :key="index"
            class="inline-flex items-center gap-1 rounded-full border border-gray-200 bg-gray-50 px-2 py-0.5 text-xs transition-colors hover:border-gray-300 hover:bg-gray-100"
            :title="reaction.users.join(', ')"
        >
            <span>{{ slackEmojiToUnicode(reaction.name) }}</span>
            <span class="font-medium text-gray-600">{{ reaction.count }}</span>
        </span>
    </div>
</template>
