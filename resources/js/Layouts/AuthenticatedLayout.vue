<script setup>
import { ref, watch, nextTick } from 'vue';
import { usePage } from '@inertiajs/vue3';
import Sidebar from '@/Components/Sidebar.vue';
import Header from '@/Components/Header.vue';
import Toast from '@/Components/Toast.vue';

const page = usePage();
const toastRef = ref(null);
const showingMobileMenu = ref(false);

const triggerToastRef = (msg, type) => {
    nextTick(() => {
        if (toastRef.value) {
            toastRef.value.triggerToast(msg, type);
        } else {
            console.error("Toast component not found!"); // Debugging
        }
    });
};

watch(() => page.props.flash, (flash) => {
    if (flash?.success) triggerToastRef(flash.success, 'success');
    if (flash?.error) triggerToastRef(flash.error, 'error');
}, { immediate: true, deep: true });
</script>

<template>
    <div class="min-h-screen flex bg-slate-100">
        <Toast ref="toastRef" />
        <Sidebar :show="showingMobileMenu" @close="showingMobileMenu = false" />

        <div class="flex-1 flex flex-col min-w-0">
            
            <Header :user="$page.props.auth.user" @toggleSidebar="showingMobileMenu = true">
                <template #title>
                    <slot name="header" />
                </template>
            </Header>

            <main class="p-2 md:p-9 flex-1 overflow-x-hidden">
                <slot />
            </main>
        </div>
    </div>
</template>