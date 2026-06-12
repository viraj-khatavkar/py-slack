<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        name: string;
        imageUrl?: string | null;
        sizeClass?: string;
        roundedClass?: string;
        textClass?: string;
    }>(),
    {
        imageUrl: null,
        sizeClass: 'h-9 w-9',
        roundedClass: 'rounded-md',
        textClass: 'text-sm',
    },
);

const failed = ref(false);

watch(
    () => props.imageUrl,
    () => {
        failed.value = false;
    },
);

const showImage = computed(() => Boolean(props.imageUrl) && !failed.value);
const initial = computed(() => (props.name?.charAt(0) ?? '?').toUpperCase());
</script>

<template>
    <img
        v-if="showImage"
        :src="imageUrl!"
        :alt="name"
        :title="name"
        :class="[sizeClass, roundedClass]"
        loading="lazy"
        @error="failed = true"
    />
    <div
        v-else
        :class="[sizeClass, roundedClass, textClass]"
        class="flex shrink-0 items-center justify-center bg-indigo-100 font-medium text-indigo-600"
        :title="name"
    >
        {{ initial }}
    </div>
</template>
