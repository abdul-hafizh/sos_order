<script setup>
import { Bars3Icon } from '@heroicons/vue/24/outline';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';

defineProps({
    user: Object,
});

defineEmits(['toggleSidebar']);
</script>

<template>
    <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="h-16 flex items-center px-4 md:px-8 justify-between gap-3">
            <div class="flex items-center gap-4 min-w-0">
                <button @click="$emit('toggleSidebar')" class="md:hidden p-2 -ml-2 text-gray-600 hover:bg-gray-100 rounded-lg shrink-0">
                    <Bars3Icon class="w-6 h-6" />
                </button>
                <h2 class="font-bold text-lg text-gray-800 truncate">
                    <slot name="title" />
                </h2>
            </div>

            <div class="relative shrink-0">
                <Dropdown align="right" width="48">
                    <template #trigger>
                        <button class="flex items-center gap-2 text-sm font-semibold text-gray-600 hover:text-blue-700 transition">
                            <span class="hidden sm:inline">{{ user.nama_user }}</span>
                            <svg class="h-4 w-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"/></svg>
                        </button>
                    </template>
                    <template #content>
                        <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                        <DropdownLink :href="route('logout')" method="post" as="button" class="text-red-600">Log Out</DropdownLink>
                    </template>
                </Dropdown>
            </div>
        </div>
    </header>
</template>