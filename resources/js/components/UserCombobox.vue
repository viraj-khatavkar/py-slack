<script setup lang="ts">
import {
    Combobox,
    ComboboxButton,
    ComboboxInput,
    ComboboxOption,
    ComboboxOptions,
} from '@headlessui/vue';
import { ChevronUpDownIcon, XMarkIcon } from '@heroicons/vue/24/outline';
import { watchDebounced } from '@vueuse/core';
import { onMounted, ref } from 'vue';
import Avatar from '@/components/ui/Avatar.vue';

interface ComboboxUser {
    id: number;
    name: string;
    image_url: string | null;
}

const props = defineProps<{
    modelValue: string | null;
    initialUser?: ComboboxUser | null;
}>();

const emit = defineEmits<{
    'update:modelValue': [value: string | null];
}>();

const selected = ref<ComboboxUser | null>(props.initialUser ?? null);
const query = ref('');
const options = ref<ComboboxUser[]>([]);

async function fetchOptions(search: string): Promise<void> {
    const response = await fetch(
        `/api/users/search?q=${encodeURIComponent(search)}`,
    );

    if (response.ok) {
        options.value = (await response.json()) as ComboboxUser[];
    }
}

onMounted(() => void fetchOptions(''));

watchDebounced(query, (value) => void fetchOptions(value), { debounce: 250 });

function select(user: ComboboxUser | null): void {
    selected.value = user;
    emit('update:modelValue', user ? String(user.id) : null);
}

function clear(): void {
    query.value = '';
    select(null);
}
</script>

<template>
    <Combobox :model-value="selected" nullable @update:model-value="select">
        <div class="relative">
            <div class="relative">
                <ComboboxInput
                    class="block w-full rounded-md border-gray-300 pr-16 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                    placeholder="Anyone"
                    autocomplete="off"
                    :display-value="
                        (user) => (user as ComboboxUser | null)?.name ?? ''
                    "
                    @change="query = $event.target.value"
                />
                <div
                    class="absolute inset-y-0 right-0 flex items-center gap-0.5 pr-2"
                >
                    <button
                        v-if="selected"
                        type="button"
                        class="rounded p-0.5 text-gray-400 hover:text-gray-600"
                        aria-label="Clear author filter"
                        @click="clear"
                    >
                        <XMarkIcon class="h-4 w-4" aria-hidden="true" />
                    </button>
                    <ComboboxButton
                        class="flex items-center"
                        aria-label="Show authors"
                    >
                        <ChevronUpDownIcon
                            class="h-5 w-5 text-gray-400"
                            aria-hidden="true"
                        />
                    </ComboboxButton>
                </div>
            </div>

            <ComboboxOptions
                class="absolute z-30 mt-1 max-h-60 w-full overflow-auto rounded-md bg-white py-1 text-sm shadow-lg ring-1 ring-black/5 focus:outline-none"
            >
                <div
                    v-if="options.length === 0"
                    class="px-3 py-2 text-gray-500"
                >
                    No users found.
                </div>
                <ComboboxOption
                    v-for="user in options"
                    :key="user.id"
                    v-slot="{ active, selected: isSelected }"
                    :value="user"
                    as="template"
                >
                    <li
                        class="flex cursor-pointer items-center gap-2 px-3 py-2"
                        :class="
                            active
                                ? 'bg-indigo-50 text-indigo-900'
                                : 'text-gray-900'
                        "
                    >
                        <Avatar
                            :name="user.name"
                            :image-url="user.image_url"
                            size-class="h-6 w-6"
                            text-class="text-[10px]"
                        />
                        <span
                            class="truncate"
                            :class="{ 'font-semibold': isSelected }"
                            >{{ user.name }}</span
                        >
                    </li>
                </ComboboxOption>
            </ComboboxOptions>
        </div>
    </Combobox>
</template>
